<?php

declare(strict_types=1);

namespace DataplanStandard\Sniffs\PHP;


use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Files\File;

/**
 * Checks for declare(strict_types=1) in all php files.
 *
 * This only checks for the statemenet existing and not that it has been used correctly
 */
class RequireStrictTypesSniff implements Sniff {

	public function register() {
		return [
			T_OPEN_TAG,
		];
	}

	public function process(File $phpcsFile, $stackPtr) {
		$tokens = $phpcsFile->getTokens();

		$startPos = $phpcsFile->findNext([ T_DECLARE ], $stackPtr + 1);

		if ($startPos === false) {
			$phpcsFile->addError('declare statement not found in file', $stackPtr, 'NoDeclare');
		} else {
			$endPos = $tokens[$startPos]['parenthesis_closer'];
			$text = $phpcsFile->getTokensAsString($startPos, ($endPos - $startPos + 1));

			if ($text !== 'declare(strict_types=1)') {
				$phpcsFile->addError('declare strict_types statement not found in file', $stackPtr, 'NoDeclareStrictTypes');
			}
		}

		return ($phpcsFile->numTokens + 1);
	}

}
