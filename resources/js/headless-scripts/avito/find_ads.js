((elementSelector, xPath) => {
    if (!xPath) {
        const elements = Array.from(document.querySelectorAll(elementSelector));
        if (elements.length === 0) return false;
        return elements.map(el => el.textContent);
    } else {
        // XPath: evaluate and collect all matching nodes
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
            if (node && node.textContent !== undefined) {
                results.push({ "text" : node.textContent, "link" : node.getAttribute("href") });
            }
        }

        return results.length > 0 ? results : false;
    }
})('{{elementSelector}}',{{Xpath}});