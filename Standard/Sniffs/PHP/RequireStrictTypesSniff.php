<?php

class Standard_Sniffs_PHP_RequireStrictTypesSniff implements PHP_CodeSniffer_Sniff {

	public function register() {
		return [
			T_OPEN_TAG,
		];
	}

	public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr) {
		$fileData = file_get_contents($phpcsFile->getFilename());

		if (strpos($fileData, 'declare(strict_types=1);') === false) {
			$phpcsFile->addError('declare(strict_types=1); not found in file', $stackPtr, 'NoDeclare');
		}
	}

}
