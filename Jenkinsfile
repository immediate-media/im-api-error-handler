#!groovy

timestamps {
  node {
    cleanWs()
    checkout scm

    // Code quality tests+ allure code begins
    try {
      sh '/usr/local/bin/composer install -o'

      stage('PHP CodeSniffer (Linting)') {
        sh '/usr/local/bin/composer run-phpcs'
      }
      stage('PHP Lint (Syntax)') {
        sh '/usr/local/bin/composer run-phplint'
      }
      stage('PHP Mess Detector (Code Format/Complexity)') {
        sh '/usr/local/bin/composer run-phpmd'
      }
      stage('PHP Unit (Functional)') {
        sh '/usr/local/bin/composer run-phpunit'
      }
    } catch (e) {
       currentBuild.result = 'FAILURE'
    } finally {
       // Delete old build Allure report directory if it exists
       if (fileExists('allure-report')) {
          sh 'rm -rf allure-report*'
       }

       // Generate Allure reports and show in the build process
       allure commandline: 'allure_2',
          jdk: '',
          results: [
                [
                  path: 'build/allure-results'
                ]
          ]
    }
    // Code quality tests + allure code ends
  }
}
