# multi-api

## To set up, run:

1. Navigate to root folder.
2. Run 'sudo chmod -R 777 ../{CURRENT_FOLDER_NAME}'
2. Run 'composer install'
3. Run 'php jobs/generate_app.php {APP_NAME}'


### Replace {APP_NAME} AND {CURRENT_FOLDER_NAME} accordingly
### {APP_NAME} will be your main controller
Note: Memcached is required

#Important to note
* Each app generated comes with default endpoints such as below
	*POST /register 	=> register for higher level users i.e admins
	*POST /login		=> login for higher level users, returns session token to be used in POST /getUser
	*GET /getUser	=> returns user details and settings

	*POST /registerCustomer 	=> register customer
	*POST /loginCustomer		=> login for higher level users, returns session token to be used in POST /getUser
	*GET /getCustomer	=> returns user details and settings

* The above endpoints are few of the default endpoints, please feel free to explore

* User settings and customer settings can be added to and removed from using the CustomerSettings.php and UserSettings.php modules

	
