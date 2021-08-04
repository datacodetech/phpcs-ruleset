<?php

declare(strict_types=1);

namespace DatacodeStandard\Sniffs\Commenting;

use PHP_CodeSniffer\Standards\Squiz\Sniffs\Commenting\FunctionCommentSniff as SquizFunctionCommentSniff;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Parses and verifies the doc comments for functions.
 *
 * Same as the Squiz standard, but skips validation for @inheritDoc comments
 * Also scans for phancy returns where they should be e.g. `@return array{0: string}`
 */
class FunctionCommentSniff extends SquizFunctionCommentSniff
{
	/**
	 * Processes this test, when one of its tokens is encountered.
	 *
	 * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
	 * @param integer $stackPtr The position of the current token in the stack passed in $tokens.
	 *
	 * @return void
	 */
	public function process(File $phpcsFile, $stackPtr)
	{
		$tokens = $phpcsFile->getTokens();

		$find = Tokens::$methodPrefixes;
		$find[] = T_WHITESPACE;

		$commentEnd = $phpcsFile->findPrevious($find, ($stackPtr - 1), null, true);

		if (isset($tokens[$commentEnd]['comment_opener'])) {
			$commentStart = $tokens[$commentEnd]['comment_opener'];

			$commentText = $phpcsFile->getTokensAsString($commentStart, ($commentEnd - $commentStart + 1));
			$commentLines = array_map('trim', explode("\n", $commentText));

			if (count($commentLines) === 3 && $commentLines[1] === '* @inheritDoc') {
				return;
			} else {
				$this->checkFixReturnType($phpcsFile, $commentStart, $commentEnd);
			}
		}

		parent::process($phpcsFile, $stackPtr);
	}

	/**
	 * Checks and fixes the return type in case its a fancy phan
	 *
	 * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
	 * @param integer $commentStart The position of the comment start
	 * @param integer $commentEnd The position of the comment end
	 *
	 * @return void
	 */
	public function checkFixReturnType(File $phpcsFile, int $commentStart, int $commentEnd)
	{
		$tokens = $phpcsFile->getTokens();

		$returnTag = $phpcsFile->findNext([T_DOC_COMMENT_TAG], $commentStart, $commentEnd, false, '@return');
		$returnType = $phpcsFile->findNext([T_DOC_COMMENT_STRING], $returnTag, ($returnTag + 3));

		$returnTypeText = $tokens[$returnType]['content'];

		$isPhancyReturn = \str_contains($returnTypeText, 'array{') || \str_contains($returnTypeText, 'array<');

		if ($isPhancyReturn) {
			$this->fixPhancyType($phpcsFile, $commentStart, $commentEnd, $returnTag, $returnType);
		}
	}

	/**
	 * Checks and fixes the tag type in case its a fancy phan
	 *
	 * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
	 * @param integer $commentStart The position of the comment start
	 * @param integer $commentEnd The position of the comment end
	 * @param integer $stackPtrTag The position of the tag
	 * @param integer $strackPtrValue The position of the value
	 *
	 * @return void
	 */
	public function fixPhancyType(
		File $phpcsFile,
		int $commentStart,
		int $commentEnd,
		int $stackPtrTag,
		int $strackPtrValue,
		$type = 'return'
	) {
		$capitalisedType = \ucfirst($type);
		$tokens = $phpcsFile->getTokens();
		$fix = $phpcsFile->addFixableError(
			"Fancy phan {$type} found at normal {$type} type in doc comment",
			$strackPtrValue,
			"FancyPhan{$capitalisedType}TypeFound"
		);

		if ($fix) {
			$startStar = $phpcsFile->findPrevious([T_DOC_COMMENT_STAR], $stackPtrTag, $commentStart);
			$lineSpace = $phpcsFile->findPrevious([T_DOC_COMMENT_WHITESPACE], $startStar, $startStar - 1);

			$commentText = $phpcsFile->getTokensAsString($commentStart, ($commentEnd - $commentStart + 1));
			$commentLines = \array_map('trim', \explode("\n", $commentText));

			$returnLineNo = $tokens[$strackPtrValue]['line'];
			$commentStartNo = $tokens[$commentStart]['line'];

			$indentCount = $tokens[$lineSpace]['length'];
			$indent = \str_pad('', $indentCount, ' ');

			$commentLine = $commentLines[$returnLineNo - $commentStartNo];

			$phanCommentLine = \str_replace("@{$type}", "@phan-{$type}", $commentLine);

			$phpcsFile->fixer->replaceToken($strackPtrValue, "array\n{$indent}{$phanCommentLine}");
		}
	}

}
