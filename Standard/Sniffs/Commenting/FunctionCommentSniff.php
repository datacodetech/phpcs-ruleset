<?php

declare(strict_types=1);

use PHP_CodeSniffer\Standards\Squiz\Sniffs\Commenting\FunctionCommentSniff;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Parses and verifies the doc comments for functions.
 *
 * Same as the Squiz standard, but skips validation for @inheritDoc comments
 */
class Standard_Sniffs_Commenting_FunctionCommentSniff extends FunctionCommentSniff {

	/**
	 * Processes this test, when one of its tokens is encountered.
	 *
	 * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
	 * @param integer $stackPtr The position of the current token in the stack passed in $tokens.
	 *
	 * @return void
	 */
	public function process(File $phpcsFile, $stackPtr) {
		$tokens = $phpcsFile->getTokens();

		$find  = Tokens::$methodPrefixes;
		$find[] = T_WHITESPACE;

		$commentEnd = $phpcsFile->findPrevious($find, ($stackPtr - 1), null, true);

		if (isset($tokens[$commentEnd]['comment_opener'])) {
			$commentStart = $tokens[$commentEnd]['comment_opener'];

			$commentText = $phpcsFile->getTokensAsString($commentStart, ($commentEnd - $commentStart + 1));
			$commentLines = array_map('trim', explode("\n", $commentText));

			if (count($commentLines) === 3 && $commentLines[1] === '* @inheritDoc') {
				return;
			}
		}

		parent::process($phpcsFile, $stackPtr);
	}

}
