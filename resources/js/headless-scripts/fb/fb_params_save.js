const newProto = navigator.__proto__;
delete newProto.webdriver;
navigator.__proto__ = newProto;
let oldXHRSend = window.XMLHttpRequest.prototype.send;
let oldXHROpen = window.XMLHttpRequest.prototype.open;
params = null;
_params = null;
window.XMLHttpRequest.prototype.open = function(method, url, async, user, password) {
     this._url = url;
     this._goodRequest = false;
     this.addEventListener("readystatechange", function() {
        if (this._url.contains('async/search_ads')) {
            let obj = null;
            try {
                obj = JSON.parse(this.responseText.replace('for (;;);', ''));
                if (obj.payload && obj.payload.pageResults) {
                    console.log('good request');
                    params = _params;
                }
            }
            catch (e) {

            }
        }
        return true;
     }, false);
     return oldXHROpen.apply(this, arguments);
 };

 window.XMLHttpRequest.prototype.send = function(body) {
    if (this._url.contains('async/search_ads')) {
        _params = body;
    }
    return oldXHRSend.apply(this, arguments);
};