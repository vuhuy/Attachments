<?php
use MediaWiki\MediaWikiServices;

class AttachmentsHooks {
	public static function onParserFirstCallInit( Parser $parser ) {
		$parser->setFunctionHook('attach', [ self::class, 'renderAttach' ]);
		$parser->setFunctionHook('exturl', [ self::class, 'renderExtURL' ]);
		$parser->setFunctionHook('fileprefix', [ self::class, 'renderFilePrefix'], SFH_NO_HASH);
		$parser->setFunctionHook('attachments ignore subpages', [ self::class, 'renderAttachmentsIgnoreSubpages']);
	}

	private static function msg($msg, $class=''){
		return "* <big class='mw-ext-attachments $class'>$msg</big>";
	}

	public static function renderAttach( Parser $parser, $page) {
		$title = Title::newFromText($page);
		$parser->getOutput()->setPageProperty(Attachments::getAttachPropname($title), json_encode($title));

		$parser->getOutput()->setPageProperty(Attachments::PROP_ATTACH, true); # allow querying with API:Pageswithprop
		if ($parser->getTitle()->inNamespace(NS_FILE))
			# add category for $wgCountCategorizedImagesAsUsed
			$parser->addTrackingCategory('attachments-category-attached-files', $parser->getTitle());

		$parser->getLinkRenderer()->setForceArticlePath(true);
		return [self::msg(wfMessage('attached-to').' <b>'.$parser->getLinkRenderer()->makeKnownLink($title, null, [], ['redirect'=>'no']).'</b>'), 'isHTML'=>true];
	}

	public static function renderExtURL( Parser $parser, $url) {
		$out = $parser->getOutput();
		if ($out->getExtensionData('did-exturl')){
			$parser->addTrackingCategory('attachments-category-exturl-error', $parser->getTitle());
			return self::msg(wfMessage('attachments-exturl-twice'), 'error');
		}

		$out->setExtensionData('did-exturl', true);
		$status = Attachments::validateURL($url);

		if ($status === true){
			$out->setPageProperty(Attachments::PROP_URL, $url);
			return self::msg("&rarr; $url");
		} else {
			$out->setPageProperty(Attachments::PROP_URL, 'invalid');
			$parser->addTrackingCategory('attachments-category-exturl-error', $parser->getTitle());
			return self::msg($status.' '.wfEscapeWikiText($url), 'error');
		}
	}

	public static function renderFilePrefix( Parser $parser, $path) {
		$level = substr_count($path.'/', '../');
		$parts = explode('/', $parser->getTitle()->getPrefixedText(), 25);
		return Attachments::getFilePrefix(implode('/', array_slice($parts, 0, count($parts)-$level)));
	}

	public static function renderAttachmentsIgnoreSubpages(Parser $parser, $prefix){
		$value = Title::newFromText($parser->getStripState()->unstripBoth($prefix))->getDBKey();
		$parser->getOutput()->setPageProperty(Attachments::PROP_IGNORE_SUBPAGES, json_encode($value));
	}

	public static function onBeforePageDisplay( OutputPage $out, Skin $skin ) {
		if (!Attachments::isViewingApplicablePage($out)) return true;

		$config = MediaWikiServices::getInstance()->getMainConfig();
		$attachmentsShowEmptySection = $config->get( 'AttachmentsShowEmptySection' );

		$title = $out->getTitle();
		$pages = Attachments::getPages($title);
		$files = Attachments::getFiles($title);
		$html = Attachments::makeList($title, $pages, $files, $out->getContext());
		$out->getOutput()->addModuleStyles([
			'mediawiki.action.view.categoryPage.styles'
		]);

		if (count($pages)+count($files) > 0 || $attachmentsShowEmptySection){
			$out->addHTML("<div id=mw-ext-attachments class=mw-parser-output>"); # class for external link icon
			$out->addWikiTextAsInterface("== ".$out->msg('attachments')."==");

			if ($skin->getSkinName() == 'minerva' && substr($out->mBodytext, -6) == '</div>')
				# hack to make section collapsible (removing </div>)
				$out->mBodytext = substr($out->mBodytext, 0, -6);

			$out->addHTML($html);
			if ($skin->getSkinName() == 'minerva')
				$out->addHTML('</div>');
			$out->addHTML("</div>");
		}
		if ($skin->getSkinName() == 'minerva')
			$out->addModules('ext.attachments.minerva-icon');
	}

	public static function onMinervaPreRender( MinervaTemplate $tpl ) {
		if (!Attachments::isViewingApplicablePage($tpl->getSkin()) || Attachments::hasExtURL($tpl->getSkin()->getTitle()))
			return;

		$config = MediaWikiServices::getInstance()->getMainConfig();
		$attachmentsShowEmptySection = $config->get( 'AttachmentsShowEmptySection' );
		$title = $tpl->getSkin()->getTitle();

		if (Attachments::countAttachments($title) > 0 || $attachmentsShowEmptySection)
			$tpl->data['page_actions']['attachments'] = [
				'itemtitle' => $tpl->msg('attachments'),
				'href' => '#' . Sanitizer::escapeIdForAttribute($tpl->msg('attachments')),
				'class' => 'mw-ui-icon mw-ui-icon-element mw-ui-icon-minerva-attachments'
			];

		$tpl->data['page_actions']['attach'] = [
			'itemtitle' => $tpl->msg('attachments-add-new'),
			'href' => $title->getLocalURL('action=attach'),
			'class' => 'mw-ui-icon mw-ui-icon-element mw-ui-icon-minerva-attach'
		];
	}

	public static function onSkinTemplateNavigationUniversal( SkinTemplate &$sktemplate, array &$links ) {
		if (!Attachments::isViewingApplicablePage($sktemplate) || Attachments::hasExtURL($sktemplate->getTitle()))
			return;

		$config = MediaWikiServices::getInstance()->getMainConfig();
		$attachmentsShowEmptySection = $config->get( 'AttachmentsShowEmptySection' );
		$attachmentsShowInNamespaces = $config->get( 'AttachmentsShowInNamespaces' );
		$attachmentsShowInViews = $config->get( 'AttachmentsShowInViews' );
		$title = $sktemplate->getTitle();

		$count = Attachments::countAttachments($title);
		if ($attachmentsShowInNamespaces && ($count > 0 || $attachmentsShowEmptySection))
			$links['namespaces'] = array_slice($links['namespaces'], 0, 1) + [
				'attachments' => [
					'text'=> $sktemplate->msg('attachments') . " ($count)",
					'href' => '#mw-ext-attachments'
				]
			] + array_slice($links['namespaces'], 1);
		if ($attachmentsShowInViews)
			$links['views'] = array_slice($links['views'], 0, 2) + [
				'add_attachment' => [
					'text'=> $sktemplate->msg('attachments-verb'),
					'href' => $title->getLocalURL('action=attach'),
					'class' => ''
				]
			] + array_slice($links['views'], 2);
		$links['actions']['add_attachment'] = [
			'text' => $sktemplate->msg('attachments-verb')->text(),
			'href' => $title->getLocalURL(['action' => 'attach']),
			'class' => ''
		];
		return true;
	}

	public static function onListDefinedTags( &$tags ) {
		$tags[] = 'attachments-add-exturl';
	}

	public static function onMagicWordwgVariableIDs( &$customVariableIds ) {
		$customVariableIds[] = 'fileprefix';
	}
	public static function onParserGetVariableValueSwitch( &$parser, &$cache, &$magicWordId, &$ret, &$frame ) {
		if ($magicWordId == 'fileprefix')
			$ret = Attachments::getFilePrefix($parser->getTitle()->getPrefixedText());
		return true;
	}
}
