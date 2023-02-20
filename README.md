<p align="center">
    <a href="https://canonizer.com" target="_blank" style="border-width:0;"><img src="https://canonizer-public-file.s3.us-east-2.amazonaws.com/site-images/logo.svg" alt="Canonizer" /></a>
    <br>
    <span style="font-size:12px;">Version: 3.0</span>
</p>

<p align="center">
    <a href="https://canonizer.com/" target="_blank" style="color: #FFF;">View Demo</a>
    ·
    <a href="https://github.com/the-canonizer/canonizer-3.0-api/issues" target="_blank" style="color: #FFF;">Report Bug</a>
    ·
    <a href="https://github.com/the-canonizer/canonizer-3.0-api/issues" target="_blank" style="color: #FFF;">Request New Feature</a>
</p>

<!-- Table of content -->
# Objective
This document helps the contributor to understand the high level understanding of the project and setup the development environment into their machine. This is intended for the developers. 

<!-- About Project Section -->
# About the Project
A wiki system that solves the critical liabilities of Wikipedia. It solves petty "edit wars" by providing contributors the ability to create and join camps and present their views without having them immediately erased. It also provides ways to standardise definitions and vocabulary, especially important in new fields.

## Dependent Modules & Services
- Canonizer Frontend
- Canonizer Service (Please refer the [document](https://docs.google.com/document/d/1jXzw8SgIir5Mq1Gr_zpYIe8SF8gso4T2/edit?usp=share_link&ouid=102075822814629424227&rtpof=true&sd=true) for more details.)
- Mailtrap (Email delivery platform for sandbox environment)
- SendinBlue (Email delivery platform for production enevironment)
- Supervisord (For managing background queues on linux enviroment. Please refer the [link](https://www.digitalocean.com/community/tutorials/how-to-install-and-manage-supervisor-on-ubuntu-and-debian-vps) for details) 

## Architecture & Design
The application is designed based on SOA (Service Oriented Architectue), where all the different component of the system treated as a service and accessible by the RESTFull api. Please follow the [link](https://drive.google.com/file/d/1ByCvgzlgwuKUcOMG_OAAb2eKnWCN-HXb/view?usp=share_link) for high level architecture and design.

## Application Queuing System
Most of the long running jobs are implemented through events and jobs. Application uses the events and jobs provided by the Lumen framework. There are two types of tasks which is implemented by events and jobs.
- Caching of Topic tree in MongoDB
- Notifications (Email ans Push)

Please refer the [link](https://docs.google.com/document/d/1Ht6V4POfVhoPL4HS3iGOPAseB_Lxdhx-M9h7GjtXmqA/edit#) to see the implementation and configuration.

<!-- About Setup Section -->
# Getting Started
## Setup Development Environment
- Prerequisites
  - PHP >= 7.3
    - Open SSL PHP Extension
    - PDO PHP Extension
    - Mbstring PHP Extension
  - MySQL 8.0 
  - Git 
    (For installation, please see the [documentations](https://git-scm.com/book/en/v2/Getting-Started-Installing-Git))
  - Composer
    (For installation, please see the [documentations](https://getcomposer.org/download))
  - Access (Read/Write) on the repository

    *NOTE: You can install MAMP (MacOS), LAMP (Linux), or XAMPP (Windows) software depending upon the OS. Make sure above extensions have to be enabled.*

<!-- Installation Process -->
- Installation    
    Canonizer can be setup in two different ways, either using docker or locally. 
    
    ***On local machine***

    Following are the steps to setup the project locally
    1. Clone the repository inside any folder in the system 
        ```sh
        git clone git@github.com:the-canonizer/canonizer-3.0-api.git
        ```
    2. Change directory to project's root directory
        ```sh
        cd canonizer-3.0-api
        ```
    3. Install dependent packages using composer
        ```sh
        composer install
        ```
    4. Create a copy of .env.example named as .env in the project's root directory
        ```sh
        cp .env.example .env
        ```
    5. Update the enviroment variable of .env file

    6. Generate application key
        ```sh
        php artisan generate:key
        ```
    7. Create a MySQL database. Make sure the name should be same as mentioned in the .env file

    8. Run the migration
        ```sh
        php artisan migrate
        ```
    9. Clear the cache
        ```sh
        php artisan cache:clear
        ```
    10. Configure virtual host of Apache2 server
        - Edit Apache configuration file (httpd.conf) and update the below information  
        ```
        Listen 80
        ServerName localhost
        ```
        - Edit virtual host file and create a new virtual host 
        ```
        <VirtualHost *:80>
            ServerAdmin webmaster@dummy-host2.example.com
            DocumentRoot "<absolute path project directory>/canonizer-3.0-api/public"
            ServerName canonizer3.local
            ErrorLog "/opt/homebrew/var/log/httpd/dummy-host2.example.com-error_log"
            CustomLog "/opt/homebrew/var/log/httpd/dummy-host2.example.com-access_log" common
        </VirtualHost>
        ```
        - Edit host file
        ```
        127.0.0.1 canonizer3.local
        ``` 
        - Restart Apache 
        ```sh
        sudo service apache2 restart
        ```
    ***Docker***

    Following are the steps to setup the project using Docker
    1. Follow the step number 1, 2, 4, 5, 6, and 9 as mentioned above
    2. Run docker compose
        ```sh
        docker-compose up --build
        ```
        ```sh
        docker exec -it canonizer_api
        ```
        The above command will display the prompt of the canonizer_api container. Execute the below command from the prompt
        ```sh
        > cd /opt/canonizer/
        > composer install
        > php artisan migrate
        > php artisan cache:clear
        ```

<!-- Verfication Process -->
- Verification
  - For local setup, enter the below url on browser's address bar
    ```
    http://canonizer3.local
    ```
  - For docker setup, enter the below url on browser's address bar
    ```
    http://localhost:8000
    ```
  - Output
    ```
    Lumen (8.3.4) (Laravel Components ^8.0)
    ```
## Contribution

Contributions are what make the open source community such an amazing place to learn, inspire, and create. Any contributions you make are **greatly appreciated**.

If you have a suggestion that would make this better, please clone the repo and create a pull request. You can also simply open an issue with the tag "enhancement".
Don't forget to give the project a star! Thanks again!

### Create Branch ###

Go to the project root folder ie. canonizer-3.0-api
- Checkout the base branch and pull the latest changes
  ```sh
  git checkout development && git pull origin development
  ```
- Create a new branch of type feature/fix/hotfix. For naming convention of branch, refer the [Naming Convention document](https://docs.google.com/document/d/1qm5hqWfayHczDWOe74t-cLG7ovEJVa_jLhjICkaIjv8/edit#heading=h.ivef4du1tbl9)
  ```sh
  git checkout -b <branch name>
  git status [Optional, This is just to verify you are on the same branch that you just created]
  ```


### Commit the Changes ###

Once the changes have been done, make sure to add the new files that have created. Provide a suitable message on every commit. This helps other to understand the changes applied on a specific commit.
```sh
git add -A
git commit -m "<message>"
```

### Push the Changes ###

Before pushing any changes to the remote repository, please take a pull of the latest changes of the base branch. If there is any conflict then resolve it first and again commit the changes and then push.
```sh
git pull origin development
git commit -am "<message>" [Optional, only required if any conflicts]
git push -u origin <branch name>
```

### Create a Pull Request ###

Login to the [github.com](https://github.com/the-canonizer) and select the repository ***canonizer-3.0-api***. After that follow the below instruction
- Click on Pull Request menu option
- Click on New Pull Request button
- Select the base branch (development) and compare branch (the new branch that is to be merged on base)
- Add reviewer & Assignee
- Provide proper description, label, and issue number 
- Click on the Create Pull Request button at the bottom

## Run Test Cases

Update the exiting test cases if required or create a new test case for any new functionality. Before any pushing the changes, please verify that all the test cases are successfully passed. Run the below command from the project's root directory.
```sh
./vendor/bin/phpunit
```
For all the test functions a specific file 
```sh
./vendor/bin/phpunit --filter "<test case file name>"
```

## Help
1. Run migration
    ```sh
    php artisan migrate
    ```
2. Run a specific migration file
    ```sh
    php artisan migrate --path <file path>
    ```
3. Run a seed 
    ```sh
    php artisan db:seed
    ```
4. Run a specific seed 
    ```sh
    php artisan db:seed --class=<seeder class name>
    ```
5. Check supervisor status
    ```sh
    sudo supervisorctl status
    ```
6. Start supervisor
    ```sh
    sudo supervisorctl start all
    ```
7. Stop supervisor
    ```sh
    sudo supervisorctl stop all
    ```
8. Restart supervisor
    ```sh
    sudo supervisorctl restart all
    ```

# License

Lesser MIT License

Copyright (c) 2006-2023 Canonizer.com

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software with minimal restriction, including without limitation the rights to use, copy, modify, merge, publish, and distribute copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

Any activity arising from use under this license must maintain compliance with all related and dependent licensees.

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

# Contact
Brent Allsop - [@Brent's_twitter](https://twitter.com/your_username) - brent.allsop@gmail.com

Project Link: [https://canonizer.com](https://canonizer.com)
