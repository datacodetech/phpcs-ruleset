<?php
/**
 * CodingStandard_Sniffs_Commenting_FunctionCommentSniff.
 *
 * PHP version 5
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @author   Greg Sherwood <gsherwood@squiz.net>
 * @author   Alexander Obuhovich <aik.bold@gmail.com>
 * @license  https://github.com/aik099/CodingStandard/blob/master/LICENSE BSD 3-Clause
 * @link     https://github.com/aik099/CodingStandard
 */

// @codeCoverageIgnoreStart
if (class_exists('Squiz_Sniffs_Commenting_FunctionCommentSniff', true) === false) {
    $error = 'Class Squiz_Sniffs_Commenting_FunctionCommentSniff not found';
    throw new PHP_CodeSniffer_Exception($error);
}
// @codeCoverageIgnoreEnd

/**
 * Parses and verifies the doc comments for functions.
 *
 * Same as the Squiz standard, but skips validation for @inheritDoc comments
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @author   Greg Sherwood <gsherwood@squiz.net>
 * @author   Alexander Obuhovich <aik.bold@gmail.com>
 * @license  https://github.com/aik099/CodingStandard/blob/master/LICENSE BSD 3-Clause
 * @link     https://github.com/aik099/CodingStandard
 */
class Standard_Sniffs_Commenting_FunctionCommentSniff extends Squiz_Sniffs_Commenting_FunctionCommentSniff
{

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int                  $stackPtr  The position of the current token
     *                                        in the stack passed in $tokens.
     *
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        $find  = PHP_CodeSniffer_Tokens::$methodPrefixes;
        $find[] = T_WHITESPACE;

        $commentEnd = $phpcsFile->findPrevious($find, ($stackPtr - 1), null, true);
        $commentStart = $tokens[$commentEnd]['comment_opener'];

        $commentText = $phpcsFile->getTokensAsString($commentStart, ($commentEnd - $commentStart + 1));
        $commentLines = array_map('trim', explode("\n", $commentText));


        if (count($commentLines) === 3 && $commentLines[1] === '* @inheritDoc')
        {
            return;
        }

        parent::process($phpcsFile, $stackPtr);

    }//end process()

}//end class
