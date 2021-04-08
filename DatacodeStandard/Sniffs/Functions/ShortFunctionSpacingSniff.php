<?php

declare(strict_types=1);

namespace DatacodeStandard\Sniffs\Functions;

use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Files\File;

/**
 * Checks there is a space after a short function (fn)
 *
 * Same as the Squiz standard, but skips validation for @inheritDoc comments
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
	 * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
	 * @param integer $stackPtr The position of the current token in the stack passed in $tokens.
	 *
	 * @return integer
	 */
	public function process(File $phpcsFile, $stackPtr) {
		$tokens = $phpcsFile->getTokens();

		$shouldBeWhiteSpace = $tokens[($stackPtr + 1)];

		if ($shouldBeWhiteSpace['type'] === 'T_OPEN_PARENTHESIS') {
			if ($tokens[($stackPtr + 1)]['content'] === $phpcsFile->eolChar) {
				$spaces = 'newline';
			} else if ($tokens[($stackPtr + 1)]['code'] === T_WHITESPACE) {
				$spaces = $tokens[($stackPtr + 1)]['length'];
			} else {
				$spaces = 0;
			}

			if ($spaces !== 1) {
				$error = 'Expected 1 space after FN keyword; %s found';
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

		return ($phpcsFile->numTokens + 1);
	}

}
