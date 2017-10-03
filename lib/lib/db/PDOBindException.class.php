<?php
PackageManager::requireClassOnce('error.CustomException');

class PDOBindException extends CustomException{}