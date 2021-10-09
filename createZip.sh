echo "Create zip for plugin"

cp -R simple-jwt-login download/

cd download/
zip -r simple-jwt-login.zip simple-jwt-login

rm -rf simple-jwt-login/