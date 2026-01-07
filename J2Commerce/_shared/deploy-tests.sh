#!/bin/bash
# Deploy test infrastructure to all extensions

EXTENSIONS=(
    "plg_j2commerce_acymailing:AcyMailing Integration Plugin:plg_j2commerce_acymailing_test"
    "plg_j2commerce_productcompare:Product Compare Plugin:plg_j2commerce_productcompare_test"
    "com_j2commerce_importexport:Import/Export Component:com_j2commerce_importexport_test"
    "com_j2store_cleanup:Cleanup Component:com_j2store_cleanup_test"
    "plg_privacy_j2commerce:Privacy Plugin:plg_privacy_j2commerce_test"
    "plg_system_j2commerce_2fa:Two-Factor Authentication Plugin:plg_system_j2commerce_2fa_test"
)

for ext in "${EXTENSIONS[@]}"; do
    IFS=':' read -r dir name container <<< "$ext"
    
    echo "=== Deploying to $dir ==="
    
    # Create Dockerfile (use | as delimiter to avoid issues with / in names)
    sed "s|EXTENSION_NAME_PLACEHOLDER|$name|" _shared/Dockerfile.template > "$dir/tests/Dockerfile"
    
    # Create docker-compose.yml
    sed "s|CONTAINER_NAME_PLACEHOLDER|$container|g" _shared/docker-compose.yml.template > "$dir/tests/docker-compose.yml"
    
    # Create run-tests.sh (use | as delimiter to avoid issues with / in names)
    sed "s|CONTAINER_NAME_PLACEHOLDER|$container|g; s|EXTENSION_NAME_PLACEHOLDER|$name|g" _shared/run-tests.sh.template > "$dir/tests/run-tests.sh"
    chmod +x "$dir/tests/run-tests.sh"
    
    # Create test scripts directory
    mkdir -p "$dir/tests/scripts"
    
    # Copy test scripts based on extension type
    if [[ $dir == plg_* ]]; then
        # Plugin: use plugin-specific installation verification
        cp _shared/test-scripts/01-installation-verification.php "$dir/tests/scripts/"
        cp _shared/test-scripts/02-uninstall-verification.php "$dir/tests/scripts/"
        
        # Customize for plugin type
        folder=$(echo $dir | cut -d'_' -f2)
        element=$(echo $dir | cut -d'_' -f3-)
        sed -i "s/FOLDER_PLACEHOLDER/$folder/g; s/ELEMENT_PLACEHOLDER/$element/g" "$dir/tests/scripts/"*.php
    elif [[ $dir == com_* ]]; then
        # Component: use component-specific installation verification
        cp _shared/test-scripts/01-installation-verification-component.php "$dir/tests/scripts/01-installation-verification.php"
        cp _shared/test-scripts/02-uninstall-verification.php "$dir/tests/scripts/"
        
        # Customize for component
        component=$(echo $dir | sed 's/com_//')
        sed -i "s/COMPONENT_PLACEHOLDER/$component/g" "$dir/tests/scripts/"*.php
    fi
    
    echo "✅ Deployed to $dir"
done

echo ""
echo "=== Deployment Complete ==="
