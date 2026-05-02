<?php

use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;

class ImageCompare {

	public static function onExtensionLoad() {}

	public static function onParserFirstCallInit( Parser $parser ) {
		$parser->setHook( 'imgcomp', [ self::class, 'parseTag' ] );
	}

	public static function isOnMobile() {
		return class_exists( 'MobileContext' )
			&& \MobileContext::singleton()->isMobileDevice();
	}

	public static function parseTag( $input, array $args, Parser $parser, PPFrame $frame ) {

		try {

			$mobile = self::isOnMobile();
			$mobilewidth = 320;

			$parser->getOutput()->addModules(
				[ 'ext.imageCompare' . ( $mobile ? '.mobile' : '' ) ]
			);

			$parser->getOutput()->addModuleStyles(
				[ 'ext.imageCompare.styles' . ( $mobile ? '.mobile' : '' ) ]
			);

			if ( !isset( $args['img1'], $args['img2'] ) ) {
				throw new ImageCompareException( "error-noimg" );
			}

			$width = null;

			if ( isset( $args['width'] ) && is_numeric( $args['width'] ) ) {
				$width = (int) $args['width'];
			}

			if ( isset( $args['mobilewidth'] ) && is_numeric( $args['mobilewidth'] ) ) {
				$mobilewidth = (int) $args['mobilewidth'];
			}

			if ( $mobile ) {
				$width = $mobilewidth;
			}

			$divheight = 0;

			$t1 = Title::newFromText( 'File:' . trim( $args['img1'] ) );
			$t2 = Title::newFromText( 'File:' . trim( $args['img2'] ) );

			$out =
				self::makeImage( $parser, $t2, 'img-comp-img', $divheight, $width ) .
				self::makeImage( $parser, $t1, 'img-comp-img img-comp-overlay', $divheight, $width );

			return "<div class='img-comp-container' style='height: {$divheight}px;'>{$out}</div>";

		} catch ( ImageCompareException $e ) {
			return wfMessage( 'ImageCompare-' . $e->getMessage() )->parse();
		}
	}

	public static function makeImage( Parser $parser, Title $title, $classes, &$divheight, $width ) {

		$services = MediaWikiServices::getInstance();
		$file = $services->getRepoGroup()->findFile( $title );

		if ( !$file ) {
			return '';
		}

		$handler = $file->getHandler();

		$handlerParams = [
			'width' => $width ?: $file->getWidth()
		];

		$thumb = $file->transform( $handlerParams );

		if ( !$thumb ) {
			return '';
		}

		$height = $thumb->getHeight();

		if ( $height > $divheight ) {
			$divheight = $height;
		}

		return "<div class='float {$classes}'>" . $thumb->toHtml() . "</div>";
	}
}