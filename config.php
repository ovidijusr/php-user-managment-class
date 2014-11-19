
<?php
/*
 @all available options :
	/req - required for class
	/opt - optional

	/ example settings
		"tablename" => "test",
		"firstfield" => "test2
	/
	tablename(req): (your table name)
	firstfield(req): (identify by something like username or email)
	secondfield(req): (second field column name for password)
	tokenfield(req): (column name for your token)
	adminactivation(opt): (if = 1 then you will need to approve account before user can log in)
	method(opt): (your method POST or GET, POST is set by default)
	regdata(opt)[ 
		(inside regdata you can set properties for your columns)

		/ properties list	

		notrequired: (filter ignores this value if it is empty, false by default)
		isEmail: (checks if input is email, false by default)
		isNumeric: (checks if input numbers are numeric like 10 but not 10.5, false by default)
		maxChar: (if maxChar = 10, input can't be longer than 10 characters)
		minChar: (if minChar = 10, input can't be shorter than 10 characters)
		mustContain: (checks if input contains one of mustContain array words, example : "mustContain" => ["cat,rock"] )
		maxNumber: (if maxNumber = 10, input can't have a larger number than 10)
		minNumber: (if minNumber = 10, input can't have a smaller number than 10)
		notSymbols: (checks if input has symbols like #!@#$%^, false by default)
		formatDowncase: (formats input to downcase, example FoO to foo, false by delfault)
		
		/ example 
			"columname" => 
			[
				"setting" => true,
				"anothersetting => 5
			]		
		/
	]
 */
return
	[
		"tablename" => "test2",
		"firstfield" => "username",
		"secondfield" => "password",
		"tokenfield" => "token",
		"adminactivation" => 0,
		"method" => "post",
		"regdata" =>
			[
				"username" =>
					[
						"formatDowncase" => true,
						"notSymbols" => true,
						"minChar" => 5,
						"maxChar" => 20
					],
				"password" =>
					[
						"minChar" => 5,
						"maxChar" => 50
					],
				"age" =>
					[
						"formatDowncase" => true,
						"minNumber" => 18,
						"maxNumber" => 80
					],
			]
	];