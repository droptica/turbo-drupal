Contributing to Bitbucket Pipes
===============================

### General
Pull requests, issues and comments welcome. For pull requests:

* Add tests for new features and bug fixes

* Follow the existing style

* Separate unrelated changes into multiple pull requests

See the existing issues for things to start contributing.

For bigger changes, make sure you start a discussion first by creating an issue and explaining the intended change.

Atlassian requires contributors to sign a Contributor License Agreement, known as a CLA. This serves as a record stating that the contributor is entitled to contribute the code/documentation/translation to the project and is willing to have it used in distributions and derivative works (or is willing to transfer ownership).

Prior to accepting your contributions we ask that you please follow the appropriate
link below to digitally sign the CLA. The Corporate CLA is for those who are
contributing as a member of an organization and the individual CLA is for
those contributing as an individual.

* [CLA for corporate contributors](https://na2.docusign.net/Member/PowerFormSigning.aspx?PowerFormId=e1c17c66-ca4d-4aab-a953-2c231af4a20b)
* [CLA for individuals](https://na2.docusign.net/Member/PowerFormSigning.aspx?PowerFormId=3f94fbdc-2fbe-46ac-b14c-5d152700ae5d)

### Getting started

The basic workflow for working with pipes code is as follows:

1. Create a fork on Bitbucket. Here is a detailed insctruction how to do it: [Forking a Repository](https://confluence.atlassian.com/bitbucket/forking-a-repository-221449527.html)
2. Clone the forked repository to your local system

    ```
    git clone git@bitbucket.org:<your-account-name>/<repo-name>.git
    ```
 
3. Create a new branch

    ```
    git checkout -b feature/<new-feature>
    ```


    We encourage everyone to follow the feature branching model. See [Git Feature Branch Workflow](https://www.atlassian.com/git/tutorials/comparing-workflows/feature-branch-workflow) for more details and best practices.

4. Modify the local repository

5. Commit your changes 

    ```
    git commit -am "Add new feature"
    ```


6. Push changes back to the remote fork on Bitbucket

    ```
    git push origin feature/<new-feature>
    ```


7. Create a pull request from the forked repository (source) back to the original (destination)

### Testing

Make sure all tests are passing. Navigate to the pipelines section in your pull request and verify that all builds are green.

##### Running tests locally

Some tests require setting up a development account for the service that a pipe integrates with. Check out the **Prerequisites** section in [README.md](README.md).

To run tests locally you need to:

1. Install `bats` (bash test runner) using apt or any suitable package manager

    ```
    apt-get install bats
    ```

2. Make sure you've set up all required environment variables required for testing. Usually, these are the same variables that are required for a pipe to run.

3. Run bats
    ```
    bats test/test*
    ```

In addition to that, you can manually build and run a docker container to test your changes:

Build the image:
```
docker build -t my-test-image .
```

Run the container. Don't forget to pass in all required environment variables

```
docker run -e VAR_1=foo -e VAR_2=bar -w $(pwd) -v $(pwd):$(pwd) my-test-image
```

### Documentation

Some changes might also require a documentation update. For example, when a pipe parameter is modified, added or removed, this has to be updated in [README.md](README.md).

### Release process

This pipe uses an automated release process to bump versions using semantic versioning and generate the [CHANGELOG.md](CHANGELOG.md) file automatically. In order to automate this process it uses a tool called semversioner.

##### Step by step guide for generating a new changeset

1. Install semversioner

    ```
    pip install semversioner
    ```

2. During development phase, every change that needs to be integrated to master will need one or more changeset files. You can use semversioner to generate changeset

    ```
    semversioner add-change --type patch --description "Fix security vulnerability with authentication."
    ```

3. Make sure you commit the changeset files generated in the `.change/next-release/` folder with your code. For example:

    `git add .`
    
    `git commit -m "BP-234 FIX security issue with authentication"`
    
    `git push origin` 

4. That's it! Merge to `master` and Bitbucket Pipelines will do the rest:

    - Generate new version number based on the changeset types major, minor, patch.
    - Generate a new file in .changes directory with all the changes for this specific version.

    - (Re)generate the CHANGELOG.md file.

    - Bump the version number in README.md example and pipe.yml metadata.

    - Commit and push back to the repository.

    - Tag your commit with the new version number.

Now you're ready to start contributing to Bitbucket Pipes. Enjoy :stuck_out_tongue_winking_eye: