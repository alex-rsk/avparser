 let oldXHROpen = window.XMLHttpRequest.prototype.open;
 ratings = [];
 error = false;
 requests = 0;
 window.XMLHttpRequest.prototype.open = function(method, url, async, user, password) { 
    this.addEventListener('load', function() {
        let regexp = /web\/\d+\/user\/\w{64}\/ratings\?fromItem/;
        console.log('Ratings int-d')
        if (!url.test(regexp))
        {
            return oldXHROpen.apply(this, arguments);
        }
        let payload = this.responseText;
        try {
            let payloadObj = JSON.parse(payload);
            for (let entry in payloadObj.entries)
            {
                if (entry.type != 'rating') {
                    continue;
                }

                let ratingInfo = {
                    'title' : entry.value.itemTitle ?? '-',
                    'score' : entry.value.score ?? 0,
                };
                ratings.push(ratingInfo);                                 
            }
        }
        catch (e)
        {
            console.log(error, e)
        }
        return oldXHROpen.apply(this, arguments);
    });
}