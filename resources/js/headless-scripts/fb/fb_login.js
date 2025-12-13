(() =>  {
    
        var loginForm = () => 
        {
            var email_field = document.querySelector('input[name="email"]') || document.querySelector('#m_login_email');
            var password_field = document.querySelector('input[name="pass"]') || document.querySelector("#pass");
            var button = document.querySelector('button[name="login"]') || document.querySelector("*[data-testid='royal_login_button']");
            if (!email_field)
            {
                return {"status" : "error" , "message" : "Не могу найти поле email."};
            }

            if (!password_field )
            {
                return {"status" : "error" , "message" : "Не могу найти поле пароль."};
            }
            if (!button)
            {
                return {"status" : "error" , "message" : "Кнопка не найдена."};
            }

            email_field.value =  "{{login}}";   
            password_field.value  = "{{password}}";
            button.click();
            
            return {"status" : "success", "message" : "logged in"};
        };
        return loginForm();
})();