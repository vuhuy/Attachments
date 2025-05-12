mw.loader.using('mediawiki.util', function() {
    const data = mw.config.get( 'attachmentsMinervaWorkaround' );
    if (data && data.useWorkaround) {
        const pViews = document.querySelector("#page-actions");
        if (pViews) {
            const children = pViews.children;
            const before = children.length > 1 ? children[children.length - 1] : null;
            if (before) {
                const attachment = document.createElement("li");
                attachment.className = "page-actions-menu__list-item";
                attachment.innerHTML = `
                    <a role="button" href="${data.href}" data-mw="interface" title="${data.text}" 
                       class="menu__item--page-actions-history mw-ui-icon mw-ui-icon-element
                       mw-ui-icon-minerva-attach mw-ui-icon-with-label-desktop mw-ui-button mw-ui-quiet">
                       ${data.text}
                    </a>
                `;
                pViews.insertBefore(attachment, before);
            }   
        }
    }
});
