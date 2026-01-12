<?php
/**
 * @package     Privacy J2Commerce Plugin
 * @subpackage  Task
 * @copyright   Copyright (C) 2025 Advans IT Solutions GmbH. All rights reserved.
 * @license     Proprietary
 */

namespace Advans\Plugin\Privacy\J2Commerce\Task;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Component\Scheduler\Administrator\Event\ExecuteTaskEvent;
use Joomla\Component\Scheduler\Administrator\Task\Status;
use Joomla\Component\Scheduler\Administrator\Traits\TaskPluginTrait;
use Joomla\Event\SubscriberInterface;
use Joomla\Database\DatabaseAwareTrait;

/**
 * Automatic data cleanup task for expired retention periods
 *
 * @since  1.0.0
 */
class AutoCleanupTask implements SubscriberInterface
{
    use TaskPluginTrait;
    use DatabaseAwareTrait;

    /**
     * @var string[]
     * @since 1.0.0
     */
    private const TASKS_MAP = [
        'plg_privacy_j2commerce.autocleanup' => [
            'langConstPrefix' => 'PLG_PRIVACY_J2COMMERCE_TASK_AUTOCLEANUP',
            'method'          => 'autoCleanup',
        ],
    ];

    /**
     * Returns an array of events this subscriber will listen to.
     *
     * @return  array
     *
     * @since   1.0.0
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onTaskOptionsList'    => 'advertiseRoutines',
            'onExecuteTask'        => 'standardRoutineHandler',
            'onContentPrepareForm' => 'enhanceTaskItemForm',
        ];
    }

    /**
     * Automatic cleanup of expired user data
     *
     * @param   ExecuteTaskEvent  $event  The event
     *
     * @return  int  Task status
     *
     * @since   1.0.0
     */
    protected function autoCleanup(ExecuteTaskEvent $event): int
    {
        $this->logTask('Starting automatic data cleanup...');

        try {
            $db = $this->getDatabase();
            $retentionYears = (int) $this->params->get('retention_years', 10);
            
            // Calculate cutoff date
            $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$retentionYears} years"));
            
            $this->logTask("Retention period: {$retentionYears} years");
            $this->logTask("Cutoff date: {$cutoffDate}");
            
            // Find users with all orders older than retention period
            $query = $db->getQuery(true)
                ->select('DISTINCT o.user_id')
                ->from($db->quoteName('#__j2store_orders', 'o'))
                ->where($db->quoteName('o.user_id') . ' > 0')
                ->group($db->quoteName('o.user_id'))
                ->having('MAX(' . $db->quoteName('o.created_on') . ') < ' . $db->quote($cutoffDate));
            
            $db->setQuery($query);
            $userIds = $db->loadColumn();
            
            if (empty($userIds)) {
                $this->logTask('No users found with expired retention periods');
                return Status::OK;
            }
            
            $this->logTask('Found ' . count($userIds) . ' users with expired retention periods');
            
            $anonymizedCount = 0;
            $partialCount = 0;
            $errorCount = 0;
            
            foreach ($userIds as $userId) {
                try {
                    $hasLifetime = $this->hasLifetimeLicense($userId);
                    
                    if ($hasLifetime) {
                        // Partial anonymization: keep email for license activation
                        $this->partialAnonymizeUserData($userId);
                        $partialCount++;
                        $this->logTask("Partially anonymized data for user ID: {$userId} (has lifetime license)");
                    } else {
                        // Full anonymization
                        $this->anonymizeUserData($userId);
                        $anonymizedCount++;
                        $this->logTask("Fully anonymized data for user ID: {$userId}");
                    }
                } catch (\Exception $e) {
                    $errorCount++;
                    $this->logTask("Error anonymizing user ID {$userId}: " . $e->getMessage(), 'error');
                }
            }
            
            $this->logTask("Cleanup complete: {$anonymizedCount} fully anonymized, {$partialCount} partially anonymized, {$errorCount} errors");
            
            if ($errorCount > 0 && $anonymizedCount === 0) {
                return Status::KNOCKOUT;
            }
            
            return Status::OK;
            
        } catch (\Exception $e) {
            $this->logTask('Fatal error: ' . $e->getMessage(), 'error');
            return Status::KNOCKOUT;
        }
    }

    /**
     * Check if user has lifetime licenses via J2Store Custom Field
     *
     * @param   int  $userId  The user ID
     *
     * @return  bool
     *
     * @since   1.0.0
     */
    protected function hasLifetimeLicense(int $userId): bool
    {
        $db = $this->getDatabase();
        
        // Get all product IDs from user's orders
        $query = $db->getQuery(true)
            ->select('DISTINCT oi.product_id')
            ->from($db->quoteName('#__j2store_orders', 'o'))
            ->leftJoin(
                $db->quoteName('#__j2store_orderitems', 'oi') . 
                ' ON ' . $db->quoteName('o.j2store_order_id') . ' = ' . $db->quoteName('oi.order_id')
            )
            ->where($db->quoteName('o.user_id') . ' = :userid')
            ->where($db->quoteName('oi.product_id') . ' IS NOT NULL')
            ->bind(':userid', $userId, \Joomla\Database\ParameterType::INTEGER);
        
        $db->setQuery($query);
        $productIds = $db->loadColumn();
        
        if (empty($productIds)) {
            return false;
        }
        
        // Check if any product has the lifetime custom field set to 'Yes'
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__j2store_product_customfields'))
            ->where($db->quoteName('product_id') . ' IN (' . implode(',', array_map('intval', $productIds)) . ')')
            ->where($db->quoteName('field_name') . ' = ' . $db->quote('is_lifetime_license'))
            ->where('LOWER(TRIM(' . $db->quoteName('field_value') . ')) = ' . $db->quote('yes'));
        
        $db->setQuery($query);
        $count = $db->loadResult();
        
        return $count > 0;
    }

    /**
     * Partially anonymize user data (keep email for lifetime licenses)
     *
     * @param   int  $userId  The user ID
     *
     * @return  void
     *
     * @since   1.0.0
     */
    protected function partialAnonymizeUserData(int $userId): void
    {
        $db = $this->getDatabase();
        
        // Anonymize orders but KEEP email for license activation
        if ($this->params->get('anonymize_orders', 1)) {
            $query = $db->getQuery(true)
                ->update($db->quoteName('#__j2store_orders'))
                ->set([
                    // Keep billing_email for license activation
                    $db->quoteName('billing_first_name') . ' = ' . $db->quote('Anonymized'),
                    $db->quoteName('billing_last_name') . ' = ' . $db->quote('User'),
                    $db->quoteName('billing_phone_1') . ' = ' . $db->quote('000-000-0000'),
                    $db->quoteName('billing_phone_2') . ' = ' . $db->quote(''),
                    $db->quoteName('billing_address_1') . ' = ' . $db->quote(''),
                    $db->quoteName('billing_address_2') . ' = ' . $db->quote(''),
                    $db->quoteName('billing_city') . ' = ' . $db->quote(''),
                    $db->quoteName('billing_zip') . ' = ' . $db->quote(''),
                    $db->quoteName('shipping_email') . ' = ' . $db->quote(''),
                    $db->quoteName('shipping_first_name') . ' = ' . $db->quote(''),
                    $db->quoteName('shipping_last_name') . ' = ' . $db->quote(''),
                    $db->quoteName('shipping_phone_1') . ' = ' . $db->quote(''),
                    $db->quoteName('shipping_phone_2') . ' = ' . $db->quote(''),
                    $db->quoteName('shipping_address_1') . ' = ' . $db->quote(''),
                    $db->quoteName('shipping_address_2') . ' = ' . $db->quote(''),
                    $db->quoteName('shipping_city') . ' = ' . $db->quote(''),
                    $db->quoteName('shipping_zip') . ' = ' . $db->quote(''),
                ])
                ->where($db->quoteName('user_id') . ' = :userid')
                ->bind(':userid', $userId, \Joomla\Database\ParameterType::INTEGER);
            
            $db->setQuery($query);
            $db->execute();
        }
        
        // Delete addresses
        if ($this->params->get('delete_addresses', 1)) {
            $query = $db->getQuery(true)
                ->delete($db->quoteName('#__j2store_addresses'))
                ->where($db->quoteName('user_id') . ' = :userid')
                ->bind(':userid', $userId, \Joomla\Database\ParameterType::INTEGER);
            
            $db->setQuery($query);
            $db->execute();
        }
    }

    /**
     * Fully anonymize user data
     *
     * @param   int  $userId  The user ID
     *
     * @return  void
     *
     * @since   1.0.0
     */
    protected function anonymizeUserData(int $userId): void
    {
        $db = $this->getDatabase();
        
        // Anonymize orders
        if ($this->params->get('anonymize_orders', 1)) {
            $query = $db->getQuery(true)
                ->update($db->quoteName('#__j2store_orders'))
                ->set([
                    $db->quoteName('billing_email') . ' = ' . $db->quote('anonymized@example.com'),
                    $db->quoteName('billing_first_name') . ' = ' . $db->quote('Anonymized'),
                    $db->quoteName('billing_last_name') . ' = ' . $db->quote('User'),
                    $db->quoteName('billing_phone_1') . ' = ' . $db->quote('000-000-0000'),
                    $db->quoteName('billing_phone_2') . ' = ' . $db->quote(''),
                    $db->quoteName('billing_address_1') . ' = ' . $db->quote(''),
                    $db->quoteName('billing_address_2') . ' = ' . $db->quote(''),
                    $db->quoteName('billing_city') . ' = ' . $db->quote(''),
                    $db->quoteName('billing_zip') . ' = ' . $db->quote(''),
                    $db->quoteName('shipping_email') . ' = ' . $db->quote('anonymized@example.com'),
                    $db->quoteName('shipping_first_name') . ' = ' . $db->quote('Anonymized'),
                    $db->quoteName('shipping_last_name') . ' = ' . $db->quote('User'),
                    $db->quoteName('shipping_phone_1') . ' = ' . $db->quote('000-000-0000'),
                    $db->quoteName('shipping_phone_2') . ' = ' . $db->quote(''),
                    $db->quoteName('shipping_address_1') . ' = ' . $db->quote(''),
                    $db->quoteName('shipping_address_2') . ' = ' . $db->quote(''),
                    $db->quoteName('shipping_city') . ' = ' . $db->quote(''),
                    $db->quoteName('shipping_zip') . ' = ' . $db->quote(''),
                ])
                ->where($db->quoteName('user_id') . ' = :userid')
                ->bind(':userid', $userId, \Joomla\Database\ParameterType::INTEGER);
            
            $db->setQuery($query);
            $db->execute();
        }
        
        // Delete addresses
        if ($this->params->get('delete_addresses', 1)) {
            $query = $db->getQuery(true)
                ->delete($db->quoteName('#__j2store_addresses'))
                ->where($db->quoteName('user_id') . ' = :userid')
                ->bind(':userid', $userId, \Joomla\Database\ParameterType::INTEGER);
            
            $db->setQuery($query);
            $db->execute();
        }
    }
}
