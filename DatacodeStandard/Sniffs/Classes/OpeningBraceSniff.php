<?php

declare(strict_types=1);

namespace DatacodeStandard\Sniffs\Classes;

use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Files\File;

/**
 * Ensures no blank line after class open closure
 */
class OpeningBraceSniff implements Sniff {

	public function register() {
		return [
			T_CLASS,
			T_TRAIT,
			T_INTERFACE,
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
		$opener = $tokens[$stackPtr]['scope_opener'];
		$nextAfterOpen = $phpcsFile->findNext(T_WHITESPACE, ($opener + 1), null, true);

		if ($tokens[$nextAfterOpen]['line'] > ($tokens[$opener]['line'] + 1)) {
			// Opening brace is on a new line, so there must be no blank line after it.
			$error = 'Opening brace must not be succeeded by a blank line';
			$fix = $phpcsFile->addFixableError($error, $opener, 'OpenBraceFollowedByBlankLine');

			if ($fix === true) {
				$phpcsFile->fixer->beginChangeset();
				for ($x = ($opener + 1); $x < $nextAfterOpen; $x++) {
					if ($tokens[$x]['line'] === $tokens[$opener]['line']) {
						// Maintain existing newline.
						continue;
					}

					if ($tokens[$x]['line'] === $tokens[$nextAfterOpen]['line']) {
						// Maintain existing indent.
						break;
					}

					$phpcsFile->fixer->replaceToken($x, '');
				}

				$phpcsFile->fixer->endChangeset();
			}
		}

		return ($phpcsFile->numTokens + 1);
	}

}
