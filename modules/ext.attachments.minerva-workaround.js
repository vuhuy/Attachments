mw.loader.using('mediawiki.util', function() {
    const data = mw.config.get( 'attachmentsMinervaWorkaround' );
    if (data && data.useWorkaround) {
        const pViews = document.querySelector("#p-views");
        if (pViews) {
            const children = pViews.children;
            const before = children.length > 1 ? children[children.length - 1] : null;
            if (before) {
                const attachment = document.createElement("li");
                attachment.className = "page-actions-menu__list-item";
                attachment.innerHTML = `
                    <a role="button" href="${data.href}" data-mw="interface" title="${data.text}" 
                       class="cdx-button cdx-button--size-large cdx-button--fake-button 
                              cdx-button--fake-button--enabled cdx-button--icon-only 
                              cdx-button--weight-quiet">
                        <span class="mw-ui-icon-minerva-attach" 
                              style="min-width:20px;min-height:20px;width:1.25rem;height:1.25rem;display:inline-block;background-size:cover;background-position:center;"></span>
                        <span>${data.text}</span>
                    </a>
                `;
                pViews.insertBefore(attachment, before);
            }   
        }
    }
});