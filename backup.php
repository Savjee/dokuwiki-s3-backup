<?php

date_default_timezone_set('Europe/Brussels');

// Configure paths (No trailing slashes)
$tmp_backup_directory = '/vps.savjee.be/backup';
$path_to_wiki = '/vps.savjee.be/wiki';
$folders_to_backup = array('data', 'conf');

// Name of the backup file
$backup_name = 'backup-'. date('Y-m-d') .'.tar';

// Amazon S3 configuration
$s3_bucket = 'BUCKET NAME';
$s3_access_key = 'YOUR ACCESS KEY';
$s3_secret = 'YOUR SECRET ACCESS KEY';

// ------------
require 'S3.php';

try{
	$master_archive = new PharData($backup_name);
	
	// Tar each directory
	foreach($folders_to_backup as $folder){
		echo "Compressing folder $folder/ \n";
		$archive = new PharData($folder . '.tar');
		$archive->buildFromDirectory($path_to_wiki . '/' . $folder);
		
		$master_archive->addFile($folder . '.tar');
		unlink($folder . '.tar');
	}
	
	// Compress the entire master backup
	$master_archive->compress(Phar::GZ);
	
	unlink($backup_name);
}catch(Exception $e){
	echo "EXCEPTION: " . $e;
}

echo "Uploading to S3...\n";

$s3 = new S3($s3_access_key, $s3_secret);
$s3->putObject($s3->inputFile($backup_name.'.gz'), $s3_bucket, $backup_name.'.gz');

unlink($backup_name . '.gz');

echo "Done\n";