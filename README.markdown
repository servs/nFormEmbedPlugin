# nFormEmbedPlugin

## Introduction
nFormEmbedPlugin is a [symfony](http://symfony-project.org/) 1.3/1.4 plugin. Created to ease the embedding and saving of related [Doctrine](http://doctrine-project.org) 1.2 forms.
It key points are
* embedRelationAndCreate('RelationName');
* easy i18n embedding. Just supply the languages.

## Setup
Just extend nBaseEmbedForm from your BaseFormDoctrine form.

before: 

    class BaseFormDoctrine extends sfFormDoctrine { 
    ...

becomes:
    class BaseFormDoctrine extends nBaseEmbedForm {
    ...

## Usage
In the setup() or configure() method of a form call $this->embedRelationAndCreate('RelationName'); that's all.
Suppose we have a Doctrine model Author and a model Book.

    --- schema.yml
    Author:
      columns:
        name: string(63)
    
    Book:
      columns:
        name: string(63)
        author_id: integer
      relations:
        Author:
          local: author_id
          foreignAlias: Books

![Diagram](http://yuml.me/diagram/scruffy/class/[Author]1-0...*[Book])An Author has one or more Books. In the Author form we call the embedRelationAndCreate method. This creates a subform with all the related books and a possibility to add new books. If the AuthorForm is new, no embeddedForms will be showed, due to a bug.

    class AuthorForm extends BaseAuthorForm {
      public function configure() {
        // other stuff you might want to do...
        
        $this->embedRelationAndCreate('Books');
      }
