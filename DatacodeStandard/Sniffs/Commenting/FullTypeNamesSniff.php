<?php

declare(strict_types=1);

namespace DatacodeStandard\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Flags short scalar type names in PHPDoc generics and list forms.
 */
class FullTypeNamesSniff implements Sniff {

	/**
	 * @var array<string, string>
	 */
	private static $shortTypes = [
		'int' => 'integer',
		'bool' => 'boolean',
		'double' => 'float',
		'real' => 'float',
	];

	/**
	 * @inheritDoc
	 */
	public function register() {
		return [T_DOC_COMMENT_OPEN_TAG];
	}

	/**
	 * Find short type names in PHPDoc generics and list forms.
	 *
	 * @param File $phpcsFile The file being scanned.
	 * @param integer $stackPtr The position of the current token in the stack passed in $tokens.
	 *
	 * @return void
	 */
	public function process(File $phpcsFile, $stackPtr) {
		$tokens = $phpcsFile->getTokens();

		if (empty($tokens[$stackPtr]['comment_tags'])) {
			return;
		}

		foreach ($tokens[$stackPtr]['comment_tags'] as $tagPtr) {
			$tag = $tokens[$tagPtr]['content'];

			if (!in_array($tag, ['@param', '@return', '@var'], true)) {
				continue;
			}

			$stringPtr = $phpcsFile->findNext(
				T_DOC_COMMENT_STRING,
				$tagPtr + 1,
				$tokens[$stackPtr]['comment_closer']
			);

			if (
				$stringPtr === false
				|| $phpcsFile->findNext(T_DOC_COMMENT_TAG, $tagPtr + 1, $stringPtr) !== false
			) {
				continue;
			}

			$content = $tokens[$stringPtr]['content'];
			$type = $this->extractType($tag, ltrim($content));

			if ($type === '') {
				continue;
			}

			$suggested = preg_replace_callback(
				'/\b(int|bool|double|real)\b/i',
				static function ($matches) {
					return self::$shortTypes[strtolower($matches[1])];
				},
				$type
			);

			if ($suggested === $type) {
				continue;
			}

			$fix = $phpcsFile->addFixableError(
				'Expected "%s" but found "%s" for %s type; use full type names',
				$tagPtr,
				'ShortTypeName',
				[$suggested, $type, $tag]
			);

			if ($fix === true) {
				$trimmed = ltrim($content);
				$leading = substr($content, 0, strlen($content) - strlen($trimmed));
				$phpcsFile->fixer->replaceToken(
					$stringPtr,
					$leading . $suggested . substr($trimmed, strlen($type))
				);
			}
		}
	}

	/**
	 * Extract type from PHPDoc tag content.
	 *
	 * @param string $tag Tag name
	 * @param string $content Tag content (already left-trimmed)
	 *
	 * @return string
	 */
	private function extractType(string $tag, string $content): string {
		if ($content === '') {
			return '';
		}

		if ($tag === '@param') {
			$pos = strpos($content, '$');

			return $pos === false ? '' : rtrim(substr($content, 0, $pos));
		}

		$depth = 0;
		$length = strlen($content);

		for ($i = 0; $i < $length; $i++) {
			$char = $content[$i];

			if ($char === '<' || $char === '(') {
				$depth++;
			} elseif ($char === '>' || $char === ')') {
				$depth--;
			} elseif ($char === ' ' && $depth === 0) {
				return substr($content, 0, $i);
			}
		}

		return $content;
	}

}
