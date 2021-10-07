<?php

declare(strict_types=1);

namespace DatacodeStandard\Sniffs\Dates;

use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Files\File;

/**
 * Class: Bans instansiation of, or static methods on, evil date objects
 *
 * @see Sniff
 */
class CarbonInstansiationSniff implements Sniff {

	private static $evilClasses = [
		'Carbon',
		'CarbonImmutable',
		'DateTime',
		'DateTimeImmutable',
	];

	public function register() {
		return [
			T_NEW,
			T_STRING,
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
		$this->file = $phpcsFile;

		$tokens = $phpcsFile->getTokens();
		$firstToken = $tokens[$stackPtr];

		$classNamePointer = match ($firstToken['type']) {
			"T_STRING" => $stackPtr,
			"T_NEW" => $phpcsFile->findNext([ T_STRING ], $stackPtr + 1),
		};

		$className = $classNamePointer !== false
			? $tokens[$classNamePointer]['content']
			: null;

		if (!in_array($className, self::$evilClasses)) {
			return;
		}

		if ($firstToken['type'] === 'T_NEW') {
			$phpcsFile->addError(
				"Usage of 'new {$className}' is banned. Use the Date facade instead",
				$stackPtr,
				'NoNewCarbon',
			);
		}

		$nextTokenPtr = $this->nextNonWhitespaceToken($classNamePointer + 1);

		if (!$nextTokenPtr) {
			return;
		}

		$nextToken = $tokens[$nextTokenPtr];
		$nextTokenIsDoubleColon = $nextToken['type'] === 'T_DOUBLE_COLON';
		$probablyStaticMethodNamePtr = $this->nextNonWhitespaceToken($nextTokenPtr + 1);

		if (!$probablyStaticMethodNamePtr) {
			return;
		}

		$afterProbableStaticMethodNamePtr = $this->nextNonWhitespaceToken($probablyStaticMethodNamePtr + 1);

		if (!$afterProbableStaticMethodNamePtr) {
			return;
		}

		$thenWeAreCallingTheMethod = $tokens[$afterProbableStaticMethodNamePtr]['type'] === 'T_OPEN_PARENTHESIS';
		$isStaticMethodCall = $nextTokenIsDoubleColon && $thenWeAreCallingTheMethod;

		if ($isStaticMethodCall) {
			$phpcsFile->addError(
				"Usage of static methods on {$className} is banned. Use Date facade instead",
				$stackPtr,
				'NoCarbonStaticMethods',
			);
		}
	}

	/**
	 * Checks tokens from $start til EOF. Returns first pointer that isn't whitespace
	 *
	 * @param mixed $start The pointer we're starting at
	 *
	 * @return int|null
	 */
	private function nextNonWhitespaceToken(int $start): ?int
	{
		$tokens = $this->file->getTokens();
		$end = $this->file->numTokens;

		for ($i = $start; $i < $end; $i++) {
			if ($tokens[$i]['type'] !== 'T_WHITESPACE') {
				return $i;
			}
		}

		return false;
	}

}
