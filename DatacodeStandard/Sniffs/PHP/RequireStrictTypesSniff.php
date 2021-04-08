<?php

declare(strict_types=1);

namespace DatacodeStandard\Sniffs\PHP;

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

	/**
	 * Processes this test, when one of its tokens is encountered.
	 *
	 * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
	 * @param integer $stackPtr The position of the current token in the stack passed in $tokens.
	 *
	 * @return int
	 */
	public function process(File $phpcsFile, $stackPtr) {
		$tokens = $phpcsFile->getTokens();

		$startPos = $phpcsFile->findNext([ T_DECLARE ], $stackPtr + 1);

		if ($startPos === false) {
			$phpcsFile->addFixableError('declare statement not found in file', $stackPtr, 'NoDeclare');
			$phpcsFile->fixer->addContent($stackPtr, 'declare(strict_types=1);');
		} else {
			$endPos = $tokens[$startPos]['parenthesis_closer'];
			$text = $phpcsFile->getTokensAsString($startPos, ($endPos - $startPos + 1));

			if ($text === 'declare(strict_types=0)') {
				$phpcsFile->addFixableError('declare strict_types is set to false', $stackPtr, 'NoDeclareStrictTypes');

				$numberPos = $phpcsFile->findNext([T_LNUMBER], $startPos);
				$phpcsFile->fixer->replaceToken($numberPos, '1');
			} else if ($text !== 'declare(strict_types=1)') {
				$phpcsFile->addFixableError('declare strict_types statement not found in file', $stackPtr, 'NoDeclareStrictTypes');
				$phpcsFile->fixer->addContentBefore($startPos, 'declare(strict_types=1);');
			}
		}

		return ($phpcsFile->numTokens + 1);
	}

}
