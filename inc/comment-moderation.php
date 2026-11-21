<?php
/**
 * Bang · 评论审核（违禁词屏蔽）
 *
 * 词表 = 主题 inc/data 目录下所有 .txt（递归，含子目录），
 * 放进去就自动识别（同一个词取权重最大的那条）。
 *
 * 后台不手动输入词、不填词库路径——只把 .txt 放进 inc/data；
 * 词条写法「词」或「词=权重」（权重 1–100，缺省 1），# 开头为注释。
 *
 * 文章评论（wp_new_comment AJAX）在写入前调用 bang_moderation_handle()；
 * preprocess_comment 作为 WP 原生评论入口的兜底。
 *
 * @package Bang
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 已启用的词库文件（相对主题路径，去重后按顺序）。
 *
 * 只做一件事：递归扫描主题 `inc/data` 目录，收集里面所有 `.txt`（含子目录）。
 * 后台不填路径、不手动输入词——把 .txt 放进这个文件夹就自动识别。
 * 老的 `moderation_file` 手填路径已不再读取。
 *
 * @return string[]
 */
function bang_moderation_file_rels() {
	$out = array();
	if ( is_dir( get_template_directory() . '/inc/data' ) ) {
		$out = bang_moderation_dir_rels( 'inc/data' );
	}
	return $out;
}

/**
 * 递归收集目录下的 .txt 相对路径（含子目录）。
 *
 * @param string $rel_dir 相对主题目录。
 * @return string[]
 */
function bang_moderation_dir_rels( $rel_dir ) {
	$prefix = get_template_directory() . '/';
	$base   = $prefix . rtrim( str_replace( '\\', '/', (string) $rel_dir ), '/' );
	if ( ! is_dir( $base ) ) {
		return array();
	}
	$out   = array();
	$stack = array( $base );
	while ( $stack ) {
		$dir = array_pop( $stack );
		foreach ( (array) scandir( $dir ) as $name ) {
			if ( '.' === $name || '..' === $name ) {
				continue;
			}
			$full = $dir . '/' . $name;
			if ( is_dir( $full ) ) {
				$stack[] = $full;
			} elseif ( preg_match( '/\.txt$/i', $name ) && is_readable( $full ) ) {
				$out[] = ltrim( str_replace( $prefix, '', $full ), '/' );
			}
		}
	}
	sort( $out );
	return $out;
}

/**
 * 本请求内缓存的词表，避免每条评论都读盘。
 *
 * 词表只来自一个地方：主题 `inc/data` 目录下所有 `.txt`（递归，含子目录）。
 * 放进去就生效，不需要在后台填任何词或路径。
 *
 * 每条词的形态：array( 'w' => 词, 's' => 权重 )
 *   · 权重来源：文件里的 `词=权重`
 *   · 只写词时权重为 1；权重用于阈值累计与后台「命中最多的词」统计，不影响「包含」匹配
 *
 * @return array
 */
function bang_moderation_words() {
	static $cache = null;
	if ( null !== $cache ) {
		return $cache;
	}

	$words = array();

	/* 词库文件：inc/data 目录递归，合并去重 */
	$prefix = get_template_directory() . '/';
	foreach ( bang_moderation_file_rels() as $rel ) {
		$path = $prefix . $rel;
		if ( ! is_readable( $path ) ) {
			continue;
		}
		foreach ( bang_moderation_lines( (string) file_get_contents( $path ) ) as $line ) {
			$words[] = $line;
		}
	}

	/* 去重：同一个词取权重最大的那条 */
	$out = array();
	foreach ( $words as $item ) {
		$w = $item['w'];
		if ( ! isset( $out[ $w ] ) || $item['s'] > $out[ $w ]['s'] ) {
			$out[ $w ] = $item;
		}
	}
	$cache = array_values( $out );
	return $cache;
}

/**
 * 把一段词表文本解析成 array( array( 'w' => 词, 's' => 权重 ) )。
 * 一行一条，# 开头为注释，空行忽略；「词」或「词=权重」两种写法都认，
 * 权重取 1–100 的整数，缺省为 1。
 *
 * @param string $raw 词表文本。
 * @return array
 */
function bang_moderation_lines( $raw ) {
	$out = array();
	foreach ( preg_split( '/\r\n|\r|\n/', (string) $raw ) as $line ) {
		$line = trim( $line );
		if ( '' === $line || 0 === strpos( $line, '#' ) ) {
			continue;
		}
		$score = 1;
		$word  = $line;
		$pos   = strpos( $line, '=' );
		if ( false !== $pos ) {
			$word  = trim( substr( $line, 0, $pos ) );
			$score = absint( substr( $line, $pos + 1 ) );
		}
		$word = trim( preg_replace( '/\s+/u', ' ', $word ) );
		if ( '' === $word ) {
			continue;
		}
		$score = max( 1, min( 100, $score ? $score : 1 ) );
		$out[] = array(
			'w' => $word,
			's' => $score,
		);
	}
	return $out;
}

/**
 * 词表的纯词列表（给前端 `bangUser.words` 用）。
 * 前端只用词做「包含」提示，权重由服务端判定，所以这里只取词。
 *
 * @return string[]
 */
function bang_moderation_word_list() {
	$out = array();
	foreach ( bang_moderation_words() as $item ) {
		$out[] = $item['w'];
	}
	return $out;
}

/**
 * 词表里最大的权重（给后台「阈值」说明当上限）。
 *
 * @return int
 */
function bang_moderation_max_score() {
	$max = 1;
	foreach ( bang_moderation_words() as $item ) {
		if ( $item['s'] > $max ) {
			$max = $item['s'];
		}
	}
	return (int) $max;
}

/**
 * 统计词表在后台维护的语料里出现几次：自定义违禁词、友链申请待审列表、
 * 订阅者列表，全是站主能直接看到的地方。分数 = 命中次数 × 词的权重。
 *
 * @return array 按 score 降序，含 hits / score / w / s。
 */
function bang_moderation_stats() {
	$corpus = '';
	foreach ( (array) get_option( 'bang_link_applications', array() ) as $row ) {
		$corpus .= "\n" . ( isset( $row['name'] ) ? $row['name'] : '' ) . ' ' . ( isset( $row['desc'] ) ? $row['desc'] : '' );
	}
	foreach ( (array) get_option( 'bang_subscribers', array() ) as $row ) {
		$corpus .= "\n" . ( isset( $row['email'] ) ? $row['email'] : '' );
	}
	if ( '' === trim( $corpus ) ) {
		return array();
	}
	$hay  = function_exists( 'mb_strtolower' ) ? mb_strtolower( $corpus, 'UTF-8' ) : strtolower( $corpus );
	$rows = array();
	foreach ( bang_moderation_words() as $item ) {
		$needle = function_exists( 'mb_strtolower' ) ? mb_strtolower( $item['w'], 'UTF-8' ) : strtolower( $item['w'] );
		if ( '' === $needle ) {
			continue;
		}
		$hits = substr_count( $hay, $needle );
		if ( ! $hits ) {
			continue;
		}
		$rows[] = array(
			'w'     => $item['w'],
			's'     => $item['s'],
			'hits'  => $hits,
			'score' => $hits * $item['s'],
		);
	}
	usort(
		$rows,
		function ( $a, $b ) {
			if ( $a['score'] === $b['score'] ) {
				return $b['hits'] - $a['hits'];
			}
			return $b['score'] - $a['score'];
		}
	);
	return $rows;
}

/**
 * 扫描一段文本。大小写不敏感，中英文都按「包含」匹配。
 *
 * 权重在这里落地：命中的词按 `词=权重` 累加，总分达到「阈值」才算命中。
 * 阈值只看下面的权重，动作（reject / hold / mask）仍按后台的「命中后的动作」执行。
 * 阈值留空或为 0、或词表为空时，沿用老行为：命中任一词即算命中。
 *
 * @param string $text 原文。
 * @return array hit / words / action / masked / score / top
 */
function bang_moderation_scan( $text ) {
	$action = (string) bang_opt( 'moderation_action' );
	if ( ! in_array( $action, array( 'reject', 'hold', 'mask' ), true ) ) {
		$action = 'hold';
	}
	$out = array(
		'hit'    => false,
		'words'  => array(),
		'action' => $action,
		'masked' => (string) $text,
		'score'  => 0,
		'top'    => '',
	);
	if ( ! bang_opt( 'moderation_on' ) ) {
		return $out;
	}
	$words = bang_moderation_words();
	if ( ! $words ) {
		return $out;
	}

	/* 阈值：后台设置 1–100；留空 / 0 视为「命中即算」（老行为）。
	   有阈值时必须累计到阈值才算命中，否则单独一个低权重词也会被拦。 */
	$threshold = absint( bang_opt( 'moderation_threshold' ) );

	$text   = (string) $text;
	$hay    = function_exists( 'mb_strtolower' ) ? mb_strtolower( $text, 'UTF-8' ) : strtolower( $text );
	$masked = $text;
	$score  = 0;
	$top    = '';
	$tops   = 0;
	foreach ( $words as $item ) {
		$needle = function_exists( 'mb_strtolower' ) ? mb_strtolower( $item['w'], 'UTF-8' ) : strtolower( $item['w'] );
		if ( '' === $needle || false === strpos( $hay, $needle ) ) {
			continue;
		}
		$out['words'][] = $item['w'];
		$score         += $item['s'];
		if ( $item['s'] > $tops ) {
			$tops = $item['s'];
			$top  = $item['w'];
		}
		if ( 'mask' === $action ) {
			$masked = str_ireplace( $item['w'], '***', $masked );
		}
	}
	$out['score'] = $score;
	$out['top']   = $top;
	$out['hit']   = $out['words'] && ( $threshold < 2 || $score >= $threshold );
	$out['masked'] = $masked;
	return $out;
}

/**
 * 把命中的词拼进提示语，让「可能被屏蔽」变成「具体哪个词」。
 * 词表全是站主自己配的，这里直接显示，不给用户输入留位置。
 *
 * @param string   $message 默认提示语。
 * @param string[] $words   命中的词。
 * @param int      $score   命中总权重，>1 时一并显示（说明阈值是怎么到的）。
 * @return string
 */
function bang_moderation_hint( $message, $words, $score = 0 ) {
	$words = array_values( array_unique( array_filter( (array) $words, 'strlen' ) ) );
	if ( ! $words ) {
		return (string) $message;
	}
	$show = array_slice( $words, 0, 5 );
	$tail = (int) $score > 1 ? '，累计 ' . (int) $score . ' 分' : '';
	return (string) $message . '（命中：' . implode( '、', $show ) . $tail . '）';
}

/**
 * 统一决策入口：写入评论前调用一次。
 *
 * @param string $content 评论原文。
 * @param string $scope   可选，入口名（文章评论 / 昵称 / 友链申请 / 订阅邮箱），
 *                        填了才写「最近命中」记录。
 * @return array ok / content / hold / message / words
 */
function bang_moderation_handle( $content, $scope = '' ) {
	$scan = bang_moderation_scan( $content );
	$out  = array(
		'ok'      => true,
		'content' => (string) $content,
		'hold'    => false,
		'message' => '',
		'words'   => $scan['words'],
		'score'   => $scan['score'],
		'top'     => $scan['top'],
	);
	if ( ! $scan['hit'] ) {
		return $out;
	}
	if ( $scope && function_exists( 'bang_moderation_log' ) ) {
		bang_moderation_log( $scope, $scan );
	}
	if ( 'reject' === $scan['action'] ) {
		$out['ok']      = false;
		$out['message'] = bang_moderation_hint( '评论包含违禁词，请修改后再提交。', $scan['words'], $scan['score'] );
	} elseif ( 'mask' === $scan['action'] ) {
		$out['content'] = $scan['masked'];
	} else {
		$out['hold'] = true;
	}
	return $out;
}

/**
 * 命中后写一行记录（只读展示，不参与判定）：什么时候、哪个入口、命中哪些词、累计多少分。
 * 存在独立 option 里并限制条数，避免把 bang_options 撑大。
 *
 * @param string $scope 入口名（文章评论 / 昵称 / 友链申请 / 订阅邮箱）。
 * @param array  $scan  bang_moderation_scan() 的返回值。
 */
function bang_moderation_log( $scope, $scan ) {
	if ( empty( $scan['words'] ) ) {
		return;
	}
	$list = get_option( 'bang_mod_log', array() );
	if ( ! is_array( $list ) ) {
		$list = array();
	}
	$list[] = array(
		'time'  => current_time( 'mysql' ),
		'scope' => (string) $scope,
		'words' => array_slice( array_unique( (array) $scan['words'] ), 0, 8 ),
		'score' => (int) $scan['score'],
	);
	/* 追加写、读取时反转取最新；写入侧不再 reverse，避免多次命中后顺序错乱 */
	update_option( 'bang_mod_log', array_slice( $list, -100 ), false );
}

/**
 * 后台：违禁词面板（挂到「安全设置」tab 底部）。只读展示 + 一行说明，不参与保存。
 */
function bang_moderation_panel() {
	$files = bang_moderation_word_files();
	$words = bang_moderation_words();
	$rows  = bang_moderation_stats();
	$log   = (array) get_option( 'bang_mod_log', array() );
	$log   = array_slice( array_reverse( $log ), 0, 10 );

	$lines = array();
	$lines[] = '<p class="bang-f-d">'
		. esc_html( sprintf( '词表共 %d 条，权重最高 %d 分。只读取主题 inc/data 目录下所有 .txt（递归，含子目录），同一个词取权重最大的那条。', count( $words ), bang_moderation_max_score() ) )
		. '</p>';

	/* 已识别的文件：只读列表，放进去就生效 */
	$lines[] = '<div class="bang-f-row"><span class="bang-f-t"><label>已识别的词库文件</label>'
		. '<span class="bang-f-d">把 .txt（UTF-8，一行一词，# 开头为注释，可写「词=权重」）放进主题 inc/data 目录即自动生效，不必在后台填词或填路径；子目录里的 .txt 也会一起读。</span></span></div>';
	$lines[] = '<ul class="bang-wordfiles">';
	if ( $files ) {
		foreach ( $files as $f ) {
			$lines[] = '<li><code>' . esc_html( $f['rel'] ) . '</code><span>' . esc_html( sprintf( '%d 条', $f['count'] ) ) . '</span></li>';
		}
	} else {
		$lines[] = '<li><code>inc/data/*.txt</code><span>' . esc_html__( '还没有文件，放进去就会自动识别', 'bang' ) . '</span></li>';
	}
	$lines[] = '</ul>';

	/* 命中最多的词 */
	$lines[] = '<div class="bang-f-row"><span class="bang-f-t"><label>命中最多的词</label>'
		. '<span class="bang-f-d">按「命中次数 × 权重」排序，统计范围是后台维护的语料（友链申请待审、订阅者邮箱）。分数越高越值得留意。</span></span></div>';
	if ( $rows ) {
		$lines[] = '<table class="bang-wordstats widefat"><thead><tr><th>词</th><th>权重</th><th>命中</th><th>分数</th></tr></thead><tbody>';
		foreach ( array_slice( $rows, 0, 10 ) as $r ) {
			$lines[] = '<tr><td>' . esc_html( $r['w'] ) . '</td><td>' . esc_html( $r['s'] ) . '</td><td>' . esc_html( $r['hits'] ) . '</td><td>' . esc_html( $r['score'] ) . '</td></tr>';
		}
		$lines[] = '</tbody></table>';
	} else {
		$lines[] = '<p class="bang-f-d">' . esc_html__( '暂无统计：词表为空，或后台语料里还没出现任何命中。', 'bang' ) . '</p>';
	}

	/* 最近命中 */
	if ( $log ) {
		$lines[] = '<div class="bang-f-row"><span class="bang-f-t"><label>最近命中</label>'
			. '<span class="bang-f-d">最多保留 100 条，只读展示。</span></span></div>';
		$lines[] = '<table class="bang-wordlog widefat"><thead><tr><th>时间</th><th>入口</th><th>命中词</th><th>累计</th></tr></thead><tbody>';
		foreach ( $log as $row ) {
			$lines[] = '<tr><td>' . esc_html( isset( $row['time'] ) ? $row['time'] : '' ) . '</td>'
				. '<td>' . esc_html( isset( $row['scope'] ) ? $row['scope'] : '' ) . '</td>'
				. '<td>' . esc_html( isset( $row['words'] ) ? implode( '、', (array) $row['words'] ) : '' ) . '</td>'
				. '<td>' . esc_html( isset( $row['score'] ) ? $row['score'] : 0 ) . '</td></tr>';
		}
		$lines[] = '</tbody></table>';
	}

	return '<div class="bang-words-panel">' . implode( '', $lines ) . '</div>';
}

/**
 * 已识别的词库文件（按相对路径）。
 *
 * 与 bang_moderation_words() 共用同一套规则：递归扫描 inc/data 下所有 .txt。
 * 只给后台面板列文件名与条数，不参与判定。
 *
 * @return array 每项含 rel / count
 */
function bang_moderation_word_files() {
	$out    = array();
	$prefix = get_template_directory() . '/';
	foreach ( bang_moderation_file_rels() as $rel ) {
		$path = $prefix . $rel;
		if ( ! is_readable( $path ) ) {
			continue;
		}
		$out[] = array(
			'rel'   => $rel,
			'count' => count( bang_moderation_lines( (string) file_get_contents( $path ) ) ),
		);
	}
	return $out;
}

/**
 * 兜底：WP 原生评论入口（wp-comments-post.php、后台等）也过一遍审核。
 * 两条主链路已在写入前拦下 reject，因此这里的 wp_die 只对绕过它们的入口生效。
 *
 * @param array $commentdata 评论数据。
 * @return array
 */
function bang_moderation_preprocess( $commentdata ) {
	if ( empty( $commentdata['comment_content'] ) ) {
		return $commentdata;
	}
	$scan = bang_moderation_scan( $commentdata['comment_content'] );
	if ( ! $scan['hit'] ) {
		return $commentdata;
	}
	if ( 'mask' === $scan['action'] ) {
		$commentdata['comment_content'] = $scan['masked'];
	} elseif ( 'hold' === $scan['action'] ) {
		$commentdata['comment_approved'] = 0;
	} else {
		wp_die(
			esc_html( bang_moderation_hint( '评论包含违禁词，请修改后再提交。', $scan['words'] ) ),
			esc_html__( '评论未通过审核', 'bang' ),
			array( 'back_link' => true )
		);
	}
	return $commentdata;
}
add_filter( 'preprocess_comment', 'bang_moderation_preprocess', 5 );
