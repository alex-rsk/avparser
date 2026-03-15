(() => {
    //const elementSelector = '//*[contains(@class,"listTopBlock")]';
    const elementSelector = '//div[@data-marker="item"]';
    const result = document.evaluate(
        elementSelector,
        document,
        null,
        XPathResult.ORDERED_NODE_SNAPSHOT_TYPE,
        null
    );

    const results = [];
    for (let i = 0; i < result.snapshotLength; i++) {
        const node = result.snapshotItem(i);

        const titleNode = node.querySelector('h2[itemprop="name"]');
        const linkNode = node.querySelector('a[itemprop="url"]');
        const priceNode = node.querySelector('span[data-marker="item-price-value"]');        
        const promoted = node.querySelector('i[class*="icon_type-promoted"]');
        if (priceNode) {
            price = priceNode.textContent.replace(/\D/g, '');
        } else {
            price = 0;
        }
        
        if (node && node.textContent !== undefined) {
            results.push({ "text" : titleNode.textContent, "link" : linkNode.getAttribute("href"), "price" : price, "promoted" : promoted ? 1 : 0});
        }
    }

    return results.length > 0 ? results : false;
})();