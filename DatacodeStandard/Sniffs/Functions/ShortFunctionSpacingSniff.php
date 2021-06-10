<?php

declare(strict_types=1);

namespace DatacodeStandard\Sniffs\Functions;

use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Files\File;

/**
 * Checks there is a space after a short function (fn) and its arrow
 */
class ShortFunctionSpacingSniff implements Sniff {

	public function register() {
		return [
			T_FN,
		];
	}

	/**
	 * Processes this test, when one of its tokens is encountered.
	 *
	 * @param File $phpcsFile The file being scanned.
	 * @param integer $stackPtr The position of the current token in the stack passed in $tokens.
	 *
	 * @return void
	 */
	public function process(File $phpcsFile, $stackPtr) {
		$fnArrow = $phpcsFile->findNext(T_FN_ARROW, $stackPtr);

		$this->whiteSpaceAfter($phpcsFile, $stackPtr);
		$this->whiteSpaceAfter($phpcsFile, $fnArrow, 'FN Arrow');
	}

	/**
	 * Processes the white space after FN smell
	 *
	 * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
	 * @param integer $stackPtr The position of the current token in the stack passed in $tokens.
	 * @param string $afterType The type char the whitespace should be after to display in the error
	 *
	 * @return void
	 */
	private function whiteSpaceAfter(File $phpcsFile, $stackPtr, $afterType = 'FN'): void {
		$tokens = $phpcsFile->getTokens();
		$shouldBeWhiteSpace = $tokens[($stackPtr + 1)];

		if ($shouldBeWhiteSpace['type'] === 'T_WHITESPACE') {
			return;
		}

		$tokens = $phpcsFile->getTokens();

		if ($tokens[($stackPtr + 1)]['content'] === $phpcsFile->eolChar) {
			$spaces = 'newline';
		} else if ($tokens[($stackPtr + 1)]['code'] === T_WHITESPACE) {
			$spaces = $tokens[($stackPtr + 1)]['length'];
		} else {
			$spaces = 0;
		}

		if ($spaces !== 1) {
			$error = "Expected 1 space after {$afterType}; %s found";
			$data = [$spaces];
			$fix = $phpcsFile->addFixableError($error, $stackPtr, 'SpaceAfterFn', $data);

			if ($fix === true) {
				if ($spaces === 0) {
					$phpcsFile->fixer->addContent($stackPtr, ' ');
				} else {
					$phpcsFile->fixer->replaceToken(($stackPtr + 1), ' ');
				}
			}
		}
	}

}
