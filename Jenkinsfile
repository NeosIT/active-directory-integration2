#!/usr/bin/env groovy
library 'jenkins-pipeline-library'
 
def projectName = "next-active-directory-integration"
def useVersion = version.uniqueBuildVersion()
def buildWorkflows = ['Build and Test (Default)', 'Release latest tagged version', 'Release final version ***WARNING***']
def buildTargets = ['php_7_1', 'php_7_2', 'php_7_3']
// ID of credentials used for deploying to wordpress.org
def wordpressOrgCredentialsId = "neosit_wordpress.org"

def	pipelineConfiguration = [
	config: [
		project: "${projectName}-build",
		debug: true,
		spinnakerName: "${projectName}",
		rocketChat: [
			channel: "nextadi"
		],
		delivery: [
			files: [
				"*.zip": [
					archive: [
						enabled: true,
						mustExist: true
					],
					spinnaker: [
						type: "application/zip",
						name: "${projectName}",
						reference: "${projectName}.zip",
						version: "${useVersion}"
					]
				],
			]
		]
	]
]

def workflow = workflow.createFromPipelineConfiguration(pipelineConfiguration)
workflow.context.config["type"] = "WordPress plug-in"
def defaultPhpConfiguration = phpConfiguration.create(workflow.context)
 
pipeline {
	agent {
		label 'php'
	}
 
	parameters {
		/**
		 * When a webhook triggers, we can not specifiy which of the build targets have to run.
		 */
		choice(name: 'BUILD_WORKFLOW', choices: buildWorkflows, description: "What to do.")
	}
	
	stages {
		stage('Configure') {
			steps {
				script {
					workflow.configure(this)
				}
			}
		}
 
		stage('Checkout and import') {
			steps {
				script {
					workflow.initStage.run()
				}
			}
		}
		
		stage('Build') {
			when {
				expression {
					// only run this stage if it has been explicitly selected
					return params.BUILD_WORKFLOW == buildWorkflows[0]
				}
			}
			
			steps {
				script {
					echo "Updating composer dependencies"
					sh "${defaultPhpConfiguration.bin} ${defaultPhpConfiguration.composer} update"
					
					/**
					 * This steps creates dynamically build stages based upon the given parameters
					 * @see https://devops.stackexchange.com/a/3090
					 */
					def useTargets = buildTargets
					def builds = [:]
 
					// for each build target we create a new stage with the required build step
					for (buildTarget in useTargets) {
						def target = buildTarget
						def targetPhpConfiguration = phpConfiguration.createByProfile(target)

						builds["${target}"] = {
							node {
								label 'php'
							}
 
							stage("Build target ${target}") {
								echo "Execute quick-build"
								sh "ant \
									-Dphp=${targetPhpConfiguration.bin} \
									-Dphpunit=${targetPhpConfiguration.phpunit} \
									-DphpunitLogFile=build/logs/junit-${target}.xml \
									quick-build"
								xunit thresholds: [failed(failureNewThreshold: '0', failureThreshold: '0', unstableNewThreshold: '0', unstableThreshold: '0')], 
										tools: [PHPUnit(deleteOutputFiles: true, failIfNotNew: true, pattern: "build/logs/junit-${target}.xml", skipNoTestFiles: false, stopProcessingIfError: true)]
							}
						 }
					}
 
					// execute each build target in parallel
					parallel builds
				}
			}
		}
		
		stage('Release') {
			when {
				expression {
					// only run this stage if it has been explicitly selected
					return params.BUILD_WORKFLOW != buildWorkflows[0]
				}
			}
			
			steps {
				script {
					def subdirectory = "tags"
					// for tag deployment, we only want to checkout the "tags" folder but not every subdirectory
					def svnDepth = "immediates"
					def tmpSvnFolder = "/tmp/wordpress.org-nadi-svn"
					def svnRepository = "https://plugins.svn.wordpress.org/next-active-directory-integration"
					def gitLastTag = sh (script: "git describe --abbrev=0 --tags", returnStdout: true).trim()
					def gitLastMessage = "tagging version ${gitLastTag}"
					def workingDirectory = tmpSvnFolder
					boolean isTagDeployment = false

					if (gitLastTag == null || !gitLastTag || !gitLastTag.trim()) {
						error("No last git tag set")
					}
 
					// trunk deployment
					if (params.BUILD_WORKFLOW == buildWorkflows[2]) {
						subdirectory = "trunk"
						workingDirectory = tmpSvnFolder
						gitLastMessage = sh (script: "git log -1 --pretty=oneline", returnStdout: true).trim()
						echo "Deploying latest stable version"
						// for trunk deployment, we need to checkout any file and remove it so we can track changes.
						// A trunk deployment will always take a longer, as we have to checkout the whole trunk directory
						svnDepth = "infinity"
					}
					// tag deployment
					else {
						echo "Deploying last git tag ${gitLastTag}..."
						isTagDeployment = true
						workingDirectory = tmpSvnFolder + "/" + gitLastTag
					}

					boolean isTrunkDeployment = !isTagDeployment
					
					sh "rm -Rf ${tmpSvnFolder}"
					// @see https://jira.neos-it.de/jira/browse/ADI-677
					sh "svn checkout ${svnRepository}/${subdirectory} ${tmpSvnFolder} --depth ${svnDepth}"

					// remove anything from trunk or a previous tag
					// for a tag, we have to check the existence
					if (isTagDeployment) {
						if (fileExists(workingDirectory)) {
							sh "svn delete ${workingDirectory}"
						}
					}
					// trunk deployment, remove any file in there so we can replace it with the new version
					else {
						sh "rm -Rf ${workingDirectory}/**"
					}

					// copy everything to target folder
					sh "mkdir -p ${workingDirectory}"
					sh "cp -r * ${workingDirectory}"

					dir(workingDirectory) {
						echo "change version to ${gitLastTag}"
						sh "ant -Dversion='${gitLastTag}' set-stable-version"
						sh "ant -Dversion='${gitLastTag}' set-current-version"
	
						// build
						sh "ant -Dphp=${defaultPhpConfiguration.bin} -Dcomposer=${defaultPhpConfiguration.composer} prepare-for-publish"
						// add any files in any subdirectory
						sh "svn add --force * --auto-props --parents --depth infinity -q"
	
						// remove all missing files
						echo "remove all missing files"
						sh 'svn status | grep "^\\!" | cut -c8- | while read f; do svn rm "$f"; done'
	
						withCredentials([[$class: 'UsernamePasswordMultiBinding', credentialsId: wordpressOrgCredentialsId, usernameVariable: 'USERNAME', passwordVariable: 'PASSWORD']]) {
							echo "Committing to wordpress.org SVN repository with username ${env.USERNAME}"

							sh "svn commit -m '${gitLastMessage}' --non-interactive --no-auth-cache --username '${env.USERNAME}' --password '${env.PASSWORD}'"
						}
					}

					sh "rm -rf ${tmpSvnFolder}"
				}
			}
		}
	}
}