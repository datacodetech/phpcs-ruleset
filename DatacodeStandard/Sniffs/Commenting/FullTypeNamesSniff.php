<?php

declare(strict_types=1);

namespace DatacodeStandard\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Common;

/**
 * Flags short scalar type names in PHPDoc generics and list forms
 */
class FullTypeNamesSniff implements Sniff {

	/**
	 * Tags whose type portion should be validated.
	 *
	 * @var string[]
	 */
	private $tags = [
		'@param',
		'@return',
		'@var',
	];

	/**
	 * @inheritDoc
	 */
	public function register() {
		return [T_DOC_COMMENT_OPEN_TAG];
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
		$tokens = $phpcsFile->getTokens();

		if (!isset($tokens[$stackPtr]['comment_tags'])) {
			return;
		}

		foreach ($tokens[$stackPtr]['comment_tags'] as $tagPtr) {
			$tag = $tokens[$tagPtr]['content'];

			if (!in_array($tag, $this->tags, true)) {
				continue;
			}

			$stringPtr = $phpcsFile->findNext(
				[T_DOC_COMMENT_STRING],
				$tagPtr + 1,
				$tokens[$stackPtr]['comment_closer']
			);

			if ($stringPtr === false) {
				continue;
			}

			// Stop if we crossed into another tag.
			if ($stringPtr > $tagPtr) {
				$nextTag = $phpcsFile->findNext(
					[T_DOC_COMMENT_TAG],
					$tagPtr + 1,
					$stringPtr
				);

				if ($nextTag !== false) {
					continue;
				}
			}

			$content = $tokens[$stringPtr]['content'];
			$type = $this->extractType($tag, $content);

			if ($type === '') {
				continue;
			}

			$suggested = $this->suggestType($type);

			if ($suggested === $type) {
				continue;
			}

			// Bare / array(...) forms are already handled by Squiz FunctionComment.
			if (
				in_array($tag, ['@param', '@return'], true)
				&& Common::suggestType($type) !== $type
			) {
				continue;
			}

			$error = 'Expected "%s" but found "%s" for %s type; use full type names';
			$data = [$suggested, $type, $tag];
			$fix = $phpcsFile->addFixableError($error, $tagPtr, 'ShortTypeName', $data);

			if ($fix === true) {
				$phpcsFile->fixer->replaceToken(
					$stringPtr,
					$this->replaceType($content, $type, $suggested)
				);
			}
		}
	}

	/**
	 * Extracts the type token(s) from a tag's content string.
	 *
	 * @param string $tag The doc tag
	 * @param string $content The tag content after the tag name
	 *
	 * @return string
	 */
	private function extractType(string $tag, string $content): string {
		$content = ltrim($content);

		if ($content === '') {
			return '';
		}

		// Support array(...), array<...>, Name[], unions with |.
		// Generics may contain spaces (array<string, int>), so avoid \S+.
		$typePattern = '((?:\|?(?:array\([^)]*\)|array\s*<[^>]+>|[\\\\\\w\[\]]+))+)';

		if ($tag === '@param') {
			// @param type $name [comment]
			if (preg_match('/^' . $typePattern . '\s+\$/i', $content, $matches) !== 1) {
				return '';
			}

			return $matches[1];
		}

		// @return / @var: type then optional description (space-separated).
		if (preg_match('/^' . $typePattern . '/i', $content, $matches) !== 1) {
			return '';
		}

		return $matches[1];
	}

	/**
	 * Rebuilds the tag content with the suggested type.
	 *
	 * @param string $content Original content
	 * @param string $type Original type
	 * @param string $suggested Suggested type
	 *
	 * @return string
	 */
	private function replaceType(string $content, string $type, string $suggested): string {
		$trimmed = ltrim($content);
		$leading = substr($content, 0, strlen($content) - strlen($trimmed));

		return $leading . $suggested . substr($trimmed, strlen($type));
	}

	/**
	 * Suggests full PHPDoc type names, including inside array generics / lists.
	 *
	 * @param string $varType The declared type
	 *
	 * @return string
	 */
	private function suggestType(string $varType): string {
		if ($varType === '') {
			return '';
		}

		// Unions: int|bool|string
		if (strpos($varType, '|') !== false) {
			$parts = explode('|', $varType);

			return implode('|', array_map([$this, 'suggestType'], $parts));
		}

		// Nullable: ?int
		if ($varType[0] === '?') {
			return '?' . $this->suggestType(substr($varType, 1));
		}

		// array<key, value> / array<value>
		if (preg_match('/^array\s*<\s*(.+)\s*>$/i', $varType, $matches) === 1) {
			$innerParts = $this->splitGenericArgs($matches[1]);
			$suggestedParts = array_map([$this, 'suggestType'], $innerParts);

			return 'array<' . implode(', ', $suggestedParts) . '>';
		}

		// array(key => value) — delegate to Common::suggestType
		if (stripos($varType, 'array(') === 0) {
			return Common::suggestType($varType);
		}

		// list form: int[]
		if (substr($varType, -2) === '[]') {
			return $this->suggestType(substr($varType, 0, -2)) . '[]';
		}

		return Common::suggestType($varType);
	}

	/**
	 * Splits generic type args on top-level commas (not inside nested <>).
	 *
	 * @param string $args The content inside array<...>
	 *
	 * @return string[]
	 */
	private function splitGenericArgs(string $args): array {
		$parts = [];
		$depth = 0;
		$current = '';
		$length = strlen($args);

		for ($i = 0; $i < $length; $i++) {
			$char = $args[$i];

			if ($char === '<') {
				$depth++;
				$current .= $char;
				continue;
			}

			if ($char === '>') {
				$depth--;
				$current .= $char;
				continue;
			}

			if ($char === ',' && $depth === 0) {
				$parts[] = trim($current);
				$current = '';
				continue;
			}

			$current .= $char;
		}

		if (trim($current) !== '') {
			$parts[] = trim($current);
		}

		return $parts;
	}

}
