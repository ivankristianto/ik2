<?php
/**
 * Guesses the language of a legacy code block for `wp ik2 code-languages`.
 * Each rule is a strong signal checked in order; anything unsure stays plain.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\CLI;

defined( 'ABSPATH' ) || exit;

/**
 * Ordered heuristics returning a slug from src/code-highlight/languages.json, or null.
 */
class Code_Language_Detector {

	private const SHELL_COMMANDS = 'apt|apt-get|yum|dnf|brew|port|npm|npx|pnpm|yarn|bun|deno|node|git|svn|cd|ls|mkdir|rmdir|rm|cp|mv|chmod|chown|chgrp|ln|cat|tail|head|less|echo|export|source|curl|wget|ssh|ssh-keygen|scp|rsync|tar|gzip|gunzip|unzip|zip|docker|composer|wp|mysql|mysqldump|service|systemctl|crontab|sudo|su|vim|vi|nano|grep|find|sed|awk|kill|killall|ps|top|df|du|pip|pip3|make|gem|ffmpeg|openssl|adduser|useradd|passwd|iptables|ufw|certbot|apachectl|a2enmod|a2ensite|nginx|defaults|launchctl|gcloud|aws|kubectl|claude|gh|touch|which|whoami|uname|ping|dig|nslookup|traceroute|netstat|ifconfig|mount|umount|fdisk|reboot|shutdown|history|alias|set|unset|tee|xargs|php';

	/**
	 * Detect the language of a code block's plain text.
	 *
	 * @param string $code Decoded block text.
	 */
	public function detect( string $code ): ?string {
		$code = trim( $code );

		if ( '' === $code ) {
			return null;
		}

		$checks = [
			'php'        => 'is_php',
			'xml'        => 'is_xml',
			'json'       => 'is_json',
			'apacheconf' => 'is_apacheconf',
			'nginx'      => 'is_nginx',
			'bash'       => 'is_shell',
			'html'       => 'is_html',
			'sql'        => 'is_sql',
			'ini'        => 'is_ini',
			'csharp'     => 'is_csharp',
			'python'     => 'is_python',
			'css'        => 'is_css',
			'javascript' => 'is_javascript',
		];

		foreach ( $checks as $language => $method ) {
			if ( $this->$method( $code ) ) {
				return $language;
			}
		}

		return null;
	}

	/**
	 * PHP open tag, WordPress API calls, or `$var =` statements ending in `;`.
	 *
	 * @param string $code Block text.
	 */
	private function is_php( string $code ): bool {
		if ( str_contains( $code, '<?php' ) ) {
			return true;
		}

		if ( preg_match( '/\b(add_action|add_filter|add_theme_support|add_shortcode|register_\w+|wp_enqueue_\w+|get_option|update_option|apply_filters|do_action|phpinfo|print_r|var_dump|define)\s*\(/', $code ) ) {
			return true;
		}

		return (bool) preg_match( '/\$\w+\s*(=(?!=)|->|\[)/', $code ) && (bool) preg_match( '/;\s*(\/\/.*)?$/m', $code );
	}

	/**
	 * XML prolog.
	 *
	 * @param string $code Block text.
	 */
	private function is_xml( string $code ): bool {
		return str_starts_with( $code, '<?xml' );
	}

	/**
	 * Parses as a JSON object or array.
	 *
	 * @param string $code Block text.
	 */
	private function is_json( string $code ): bool {
		if ( ! in_array( $code[0], [ '{', '[' ], true ) ) {
			return false;
		}

		json_decode( $code );

		return JSON_ERROR_NONE === json_last_error();
	}

	/**
	 * Apache directives at the start of a line.
	 *
	 * @param string $code Block text.
	 */
	private function is_apacheconf( string $code ): bool {
		return (bool) preg_match( '/^\s*(RewriteEngine|RewriteRule|RewriteCond|RewriteBase|<IfModule|<VirtualHost|<Directory|<Files|<FilesMatch|AddType|AddHandler|AddOutputFilterByType|ExpiresActive|ExpiresByType|Header\s+(set|append|unset)|Options\s+[-+]?[A-Z]|LoadModule\s|Order\s+(allow|deny)|Deny from|Allow from|DirectoryIndex|ErrorDocument|php_value|php_flag|SetEnvIf|AuthType|AuthUserFile)\b/mi', $code );
	}

	/**
	 * Nginx blocks and directives.
	 *
	 * @param string $code Block text.
	 */
	private function is_nginx( string $code ): bool {
		return (bool) preg_match( '/^\s*(server\s*\{|location\s+[^{\n]*\{|server_name\s|listen\s+\d|fastcgi_\w+\s|proxy_pass\s|try_files\s|upstream\s+\w+\s*\{|gzip\s+on;|add_header\s|rewrite\s+\^)/mi', $code );
	}

	/**
	 * A shebang, or a first command line that runs a known command. Leading
	 * `# comment` lines are skipped; a `$ ` or root `# ` prompt is allowed.
	 *
	 * @param string $code Block text.
	 */
	private function is_shell( string $code ): bool {
		if ( preg_match( '/^#!\/(usr\/)?bin\/(env\s+)?(ba|z)?sh\b/', $code ) ) {
			return true;
		}

		// A known command, an executable path, or any word followed by a flag.
		$pattern = '/^(\$|#)?\s*(sudo\s+)?((' . self::SHELL_COMMANDS . ')(\s|$)|\.\/\S|\/(usr|etc|bin|sbin|opt)\/\S+(\s|$)|[a-z][\w.+-]*\s+--?[a-zA-Z])/';

		foreach ( explode( "\n", str_replace( "\r", '', $code ) ) as $line ) {
			$line = trim( $line );

			if ( preg_match( $pattern, $line ) ) {
				return true;
			}

			if ( '' !== $line && ! str_starts_with( $line, '#' ) ) {
				return false;
			}
		}

		return false;
	}

	/**
	 * Starts with an HTML tag or comment.
	 *
	 * @param string $code Block text.
	 */
	private function is_html( string $code ): bool {
		return (bool) preg_match( '/^<(!doctype|!--|html|head|body|div|p|a|span|ul|ol|li|script|style|link|meta|img|iframe|form|input|button|table|tr|td|section|article|header|footer|nav|main|h[1-6]|br|object|embed|param|video|audio|source|svg|noscript|template)\b/i', $code );
	}

	/**
	 * A recognisable SQL statement.
	 *
	 * @param string $code Block text.
	 */
	private function is_sql( string $code ): bool {
		return (bool) preg_match( '/^\s*(USE\s+\w+;|SELECT\s[\s\S]+?\sFROM\s|INSERT\s+INTO\s|UPDATE\s+\S+\s+SET\s|DELETE\s+FROM\s|CREATE\s+(TABLE|DATABASE|INDEX|USER)\s|ALTER\s+TABLE\s|DROP\s+(TABLE|DATABASE)\s|GRANT\s+\w+|SHOW\s+(TABLES|DATABASES|VARIABLES|STATUS)|OPTIMIZE\s+TABLE|REPAIR\s+TABLE|TRUNCATE\s+TABLE)/mi', $code );
	}

	/**
	 * Every non-comment line is a `key = value` pair or a `[section]`, with at least one pair.
	 *
	 * @param string $code Block text.
	 */
	private function is_ini( string $code ): bool {
		$pairs = 0;

		foreach ( explode( "\n", str_replace( "\r", '', $code ) ) as $line ) {
			$line = trim( $line );

			if ( '' === $line || str_starts_with( $line, ';' ) || str_starts_with( $line, '#' ) || preg_match( '/^\[[\w .-]+\]$/', $line ) ) {
				continue;
			}

			if ( ! preg_match( '/^[\w.-]+\s*=\s*[^=]*$/', $line ) ) {
				return false;
			}

			++$pairs;
		}

		return $pairs > 0;
	}

	/**
	 * C#-style typed member declarations or `using System`.
	 *
	 * @param string $code Block text.
	 */
	private function is_csharp( string $code ): bool {
		return (bool) preg_match( '/^\s*using\s+System|\b(public|private|protected|internal)\s+(static\s+)?(void|string|int|bool|object|class|override)\b/m', $code );
	}

	/**
	 * Python definitions and imports.
	 *
	 * @param string $code Block text.
	 */
	private function is_python( string $code ): bool {
		return (bool) preg_match( '/^\s*(def\s+\w+\(.*\):|from\s+[\w.]+\s+import\s|import\s+[\w.]+\s*$|class\s+\w+(\(.*\))?:\s*$)/m', $code );
	}

	/**
	 * A selector block containing at least one `property: value;` declaration.
	 *
	 * @param string $code Block text.
	 */
	private function is_css( string $code ): bool {
		return (bool) preg_match( '/^[^{}\n;]+\{\s*\n?\s*[a-z-]+\s*:\s*[^;{}\n]+;/mi', $code ) && ! preg_match( '/\bfunction\b|=>/', $code );
	}

	/**
	 * Common JavaScript declarations and browser globals.
	 *
	 * @param string $code Block text.
	 */
	private function is_javascript( string $code ): bool {
		return (bool) preg_match( '/\b(const|let|var)\s+\w+\s*=|\bfunction\s*\w*\s*\(|=>|\bdocument\.|\bwindow\.|\bconsole\.|\bjQuery\b|\$\(|\brequire\(|^\s*import\s.+\sfrom\s/m', $code );
	}
}
