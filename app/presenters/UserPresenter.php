<?php

namespace App\Presenters;

use App\Forms\BaseForm;
use App\Model\AuthorManager;
use Nette,
    App\Forms\SignFormFactory;


/**
 * User login/logout/register presenter.
 */
class UserPresenter extends BasePresenter {


    /**
     * @var AuthorManager
     * @inject
     */
    public $authorManager;


    /**
     * Sign-in form factory.
     * @return Nette\Application\UI\Form
     */
    protected function createComponentSignInForm() {
        $form = new BaseForm;
        $form->addText('username', 'Username:', 20, 255)
             ->setRequired('Please enter your username.');

        $form->addPassword('password', 'Password:', 20, 255)
             ->setRequired('Please enter your password.');

        $form->addCheckbox('remember', 'Keep me signed in');

        $form->addSubmit('send', 'Login')->setAttribute('class', 'btn-primary btn-large');

        $form->onSuccess[] = array($this, 'loginSucceeded');

        $form->onSuccess[] = function ($form) {
            $this->restoreRequest($this->getParameter('backlink'));
            $form->getPresenter()->redirect('Homepage:');
        };
        return $form;
    }


    public function loginSucceeded($form, $values) {
        if ($values->remember) {
            $this->user->setExpiration('14 days', FALSE);
        } else {
            $this->user->setExpiration('30 minutes', TRUE);
        }

        try {
            $this->user->login($values->username, $values->password);
        } catch (Nette\Security\AuthenticationException $e) {
            $form->addError($e->getMessage());
        }
    }




    public function actionRegister(){
        $this['signUpForm']->addSubmit('send', 'Register')->setAttribute('class', 'btn-primary btn-large');
        $this['signUpForm']->onSuccess[] = array($this, 'registerSucceeded');
    }


    protected function createComponentSignUpForm(){
        $form = new BaseForm();
        $form->addText('name', 'Username:', 20, 255)
             ->setRequired('Please enter your username.');

        $form->addPassword('password', 'Password:', 20, 255)
             ->setRequired('Please enter your password.');

        $form->addPassword('password2', 'Confirm password:', 20, 255)
             ->setRequired('Please enter your password.');

        $form->addText('mail', 'E-mail:', 20, 255)
             ->addRule(BaseForm::EMAIL, "Please enter a valid e-mail.");

        return $form;
    }


    public function registerSucceeded($form, $values){
        if($values->password !== $values->password2){
            $form->addError("Passwords don't match.");
            return;
        }

        if($this->authorManager->getByName($values->name)){
            $form->addError("Username already taken.");
            return;
        }


        $role = isset($values->role) ? $values->role : 'user';
        if(!$this->authorManager->add($values->name, $values->password, $values->mail, $role)){
            $form->addError("Registration failed. Sorry.");
            return;
        } else{
            $this->flashMessage('Registration completed successfully.', 'success');
            try {
                $this->user->login($values->name, $values->password);
            } catch (Nette\Security\AuthenticationException $e) {
                $form->addError($e->getMessage());
                return;
            }
            $this->redirect("Homepage:");
        }
    }




    public function actionLogout() {
        $this->getUser()->logout();
        $this->flashMessage('You have been signed out.');
        $this->redirect('Homepage:');
    }




    public function renderManage(){
        $this->template->users = $this->authorManager->getAll();
    }




    public function actionCreate(){
        $this['signUpForm']->addSelect('role', 'Role', ['user' => 'Regular user', 'admin' => 'Administrator'])
                           ->setDefaultValue('user');
        $this['signUpForm']->addSubmit('send', 'Create user')->setAttribute('class', 'btn-primary btn-large');
        $this['signUpForm']->onSuccess[] = array($this, 'createSucceeded');
    }

    public function createSucceeded($form, $values){
        if($values->password !== $values->password2){
            $form->addError("Passwords don't match.");
            return;
        }

        if($this->authorManager->getByName($values->name)){
            $form->addError("Username already taken.");
            return;
        }


        $role = isset($values->role) ? $values->role : 'user';
        if(!$this->authorManager->add($values->name, $values->password, $values->mail, $role)){
            $form->addError("User creation failed. Sorry.");
            return;
        } else{
            $this->flashMessage('User "'.$values->name.'" created successfully.', 'success');
            $this->redirect("User:manage");
        }
    }



    public function actionEdit($id){
        $defaults = $this->authorManager->find($id);
        if(!$defaults){
            $this->flashMessage("User with ID $id doesn't exist.", "error");
            $this->redirect("User:manage");
            return;
        }

        unset($defaults['password']);

        $this['editForm']->setDefaults($defaults);

    }


    protected function createComponentEditForm() {
        $form = new BaseForm();
        $form->addText('name', 'Username:')
             ->setRequired('Please enter new username.');

        $form->addPassword('password', 'Password:');

        $form->addPassword('password2', 'Confirm password:');

        $form->addText('mail', 'E-mail:')
             ->addRule(BaseForm::EMAIL, "Please enter a valid e-mail.");

        $form->addSelect('role', 'Role', ['user' => 'Regular user', 'admin' => 'Administrator'])
             ->setDefaultValue('user');

        $form->addSubmit('send', 'Edit')->setAttribute('class', 'btn-primary btn-large');
        $form->onSuccess[] = array($this, 'editSucceeded');

        return $form;
    }

    public function editSucceeded($form, $values){
        if(isset($values->password) && $values->password !== $values->password2){
            $form->addError("Passwords don't match.");
            return;
        }

        if(!$values->password){
            unset($values->password);
        }
        unset($values->password2);

        if($this->authorManager->update($this->getParameter('id'), (array) $values)){
            $this->flashMessage("Edit successfull.");
            $this->redirect("User:manage");
        }

    }




    public function actionDelete($id){
        if($this->authorManager->delete($id, $this->user->id)){
            $this->flashMessage("User #$id was deleted. All his posts were assigned to you.");
            $this->redirect("manage");
        } else{
            $this->flashMessage("User #$id could not be deleted.", 'error');
            $this->redirect("manage");
        }
    }

}
