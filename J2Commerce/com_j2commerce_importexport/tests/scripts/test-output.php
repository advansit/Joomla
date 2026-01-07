#!/usr/bin/env php
<?php
// Simple test to see if output works
file_put_contents('php://stderr', "Test output to STDERR\n");
file_put_contents('php://stdout', "Test output to STDOUT\n");
echo "Test output via echo\n";
exit(0);
