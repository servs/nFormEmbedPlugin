# nFormEmbedPlugin

## Introduction
nFormEmbedPlugin is a [symfony](http://symfony-project.org/) 1.3/1.4 plugin. Created to ease the embedding and saving of related [Doctrine](http://doctrine-project.org) 1.2 forms.

It key points are
 * Plain & Easy
 * embedRelationAndCreate('RelationName');
 * easy i18n embedding. Just supply the languages.


## Setup
Extend nBaseEmbedForm from your BaseFormDoctrine.

before: 
    class BaseFormDoctrine extends sfFormDoctrine { 
    ...

becomes:
    class BaseFormDoctrine extends nBaseEmbedForm {
    ...

## Usage
In the setup() or configure() method of a form call $this->embedRelationAndCreate('RelationName'); that's all.

Suppose we have a Doctrine model Author and a model Book. An Author has one or more Books. 

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

![Diagram](http://yuml.me/diagram/scruffy/class/[Author]1-0...*[Book])

In the Author form we call the embedRelationAndCreate method. This creates a subform with all the related books and a possibility to add new books.

    class AuthorForm extends BaseAuthorForm {
      public function configure() {
        // other stuff you might want to do...
        
        $this->embedRelationAndCreate('Books');
      }

## Known limitations

 *  No form will be embedded if the parent form doesn't already exists. Otherwise a Doctrine error will occure. The form first tries to save all embeddedForms but doesn't have a corresponding id for the relation field.
 *  The saveManyToMany() method tries it's best to save all list fields. However if your fields have strange names it might not work.
 *  A bit more testing with multi-part forms is needed. Not sure if the uploaded file is correctly saved. Just create a bug report.

## Credits
 *  [Nathan Bijnens](http://twitter.com/nathan_gs) ([Servs](http://servs.eu))
 *  Erik Van Kelst ([4levels](http://4levels.org))
 
## License
This work is published under the [Symfony License](http://www.symfony-project.org/license).
