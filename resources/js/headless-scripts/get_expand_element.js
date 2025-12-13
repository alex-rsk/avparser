(async () =>
        {
            let  elementPresent = function (selector) {
                let option = document.evaluate(selector, document, null, XPathResult.ANY_TYPE, null).iterateNext();
                return !!option;
            };
            let expandSelector = '//*[@id="content"]/div/div[2]/div[2]/div[1]/div/div[5]/div/div[2]/div[2]/div/div[2]';

            while (!elementPresent(expandSelector)) {
                await new Promise(r => setTimeout(r, 5));
            }
            let expandElement = document.evaluate(expandSelector, document, null, XPathResult.ANY_TYPE, null).iterateNext();
            if (expandElement)
            {
               expandElement.querySelector('a').click();
               return 1;
            } else {
               return 0;
            }
        })();