(() =>
{
    var pageId = /view_all_page_id=(\d+)/.exec(location.href)
    return pageId;
})();