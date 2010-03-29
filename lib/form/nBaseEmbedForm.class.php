<?php
/**
 *
 * @example extend your BaseFormDoctrine with nBaseEmbedForm.
 */
class nBaseEmbedForm extends sfFormDoctrine {
    /**
     * @var array Contains the relations that are loaded with embedRelationAndCreate
     */
    protected $_relations = array();

    /**
     *
     * @var array Stores the cultures in which the object is translated.
     */
    protected $_translations = array();

    /**
     *
     * @var boolean is this the embedded translation form.
     */
    protected $_isTranslationForm = false;

    /**
     *
     * @return array Relations
     */
    public function getRelations () {

        return $this->_relations;
    }

    /**
     *
     * @param boolean $translationForm
     */
    public function setIsTranslationForm($translationForm = true) {
        $this->_isTranslationForm = $translationForm;
    }

    /**
     *
     * @return boolean Is it a translation form?
     */
    public function getIsTranslationForm() {
        return $this->_isTranslationForm;
    }

    /**
     *
     * @param array $cultures
     * @param <type> $decorator
     */
    public function embedI18n($cultures, $decorator = null) {
        if (!$this->isI18n()) {
            throw new sfException(sprintf('The model "%s" is not internationalized.', $this->getModelName()));
        }

        $class = $this->getI18nFormClass();
        foreach ($cultures as $culture) {
            $i18nObject = $this->getObject()->Translation[$culture];
            $i18n = new $class($i18nObject);
            $i18n->setIsTranslationForm();

            if (false === $i18nObject->exists()) {
                unset($i18n['id'], $i18n['lang']);
            }

            $this->_translations[] = $culture;

            $this->embedForm($culture, $i18n, $decorator);
        }
    }

    /**
     * Embed a Doctrine_Collection relationship in to a form
     *
     *     [php]
     *     $userForm = new UserForm($user);
     *     $userForm->embedRelationAndCreate('Groups');
     *
     * @param  string $relationName  The name of the relation
     * @param  string $formClass     The name of the form class to use
     * @param  array  $formArguments Arguments to pass to the constructor (related object will be shifted onto the front)
     *
     * @throws InvalidArgumentException If the relationship is not a collection
     */
    public function embedRelationAndCreate($relationName, $parentForm = null, $formClass = null, $formArgs = array(), $formatter = 'table') {

        if ($this->isNew()) {
            return ;
        }

        if ($parentForm == null) {
            $parentForm = $this;
        }

        $this->_relations[] = $relationName;

        $relation = $this->getObject()->getTable()->getRelation($relationName);

        if ($relation->getType() !== Doctrine_Relation::MANY) {
            throw new InvalidArgumentException('You can only embed a relationship that is a collection.');
        }

        $r = new ReflectionClass(null === $formClass ? $relation->getClass().'Form' : $formClass);

        $subForm = new BaseForm();

        $relations = $parentForm->getObject()->getTable()->getRelations();
        $relationColumn = $relations[$relationName]['foreign'];


        foreach ($this->getObject()->$relationName as $index => $childObject) {
            $r = new ReflectionClass(null === $formClass ? get_class($childObject) .'Form' : $formClass);
            $form = $r->newInstanceArgs(array_merge(array($childObject), $formArgs));

            unset($form[$relationColumn]);

            $form->setWidget('delete', new sfWidgetFormInputCheckbox());

            $subForm->embedForm($index, $form);

            $subForm->getWidgetSchema()->setLabel($index, (string) $childObject);

            $subForm->getWidgetSchema()->setFormFormatterName($formatter);
        }


        $object = $relation->getClass();
        $childObject = new $object();

        $childObject->$relationColumn = $parentForm->getObject()->id;

        $r = new ReflectionClass(null === $formClass ? $relation->getClass() .'Form' : $formClass);
        $form = $r->newInstanceArgs(array_merge(array($childObject), $formArgs));

        $form->setWidget('create', new sfWidgetFormInputCheckbox());
        $form->setValidator('create', new sfValidatorPass());
        //$form->isNew(true);
        unset($form[$relationColumn]);

        $subForm->getWidgetSchema()->setFormFormatterName($formatter);

        $subForm->embedForm('new', $form);

        $this->embedForm($relationName, $subForm);
    }

    protected function cleanBindCreate ($relationName) {
        unset($this->widgetSchema[$relationName]['new'],
                $this->validatorSchema[$relationName]['new'],
                $this->embeddedForms[$relationName]['new'],
                $taintedValues[$relationName]['new'],
                $taintedFiles[$relationName]['new']);
    }

    protected function cleanEmbedded(&$taintedValues, $taintedFiles = null, &$widgetSchema = null, &$validatorSchema = null, &$form = null) {

        if ($form == null) {
            $form = $this;
        }
        if ($widgetSchema == null) {
            $widgetSchema = $this->widgetSchema;
        }
        if ($validatorSchema == null) {
            $validatorSchema = $this->validatorSchema;
        }

        foreach ($form->getRelations() as $index=>$relationName) {
            if (isset($taintedValues[$relationName])) {

                if (isset($taintedValues[$relationName]['new'])) {
                    if(isset($taintedValues[$relationName]['new']['create'])) {

                        $this->cleanEmbedded($taintedValues[$relationName]['new'],
                                @$taintedFiles[$relationName]['new'],
                                $widgetSchema[$relationName]['new'],
                                $validatorSchema[$relationName]['new'],
                                $form->embeddedForms[$relationName]->embeddedForms['new']);


                    } else {
                        unset($taintedValues[$relationName]['new'],
                                $taintedFiles[$relationName]['new'],
                                $validatorSchema[$relationName]['new'],
                                $widgetSchema[$relationName]['new'],
                                $form->embeddedForms[$relationName]->embeddedForms['new']);
                    }


                }

                foreach ($taintedValues[$relationName] as $index=>$subFormValue) {
                    if(isset($subFormValue['delete'])) {



                        $form->embeddedForms[$relationName]->embeddedForms[$index]->getObject()->delete();
                        $form->getObject()->unlink($relationName, $form->embeddedForms[$relationName]->embeddedForms[$index]->getObject()->id);

                        unset(
                                $widgetSchema[$relationName][$index],
                                $validatorSchema[$relationName][$index],
                                $form->embeddedForms[$relationName]->widgetSchema[$index],
                                $form->embeddedForms[$relationName]->validatorSchema[$index],
                                $form->embeddedForms[$relationName]->embeddedForms[$index],
                                $taintedValues[$relationName][$index],
                                $taintedFiles[$relationName][$index]);


                    } else {
                        $this->cleanEmbedded($taintedValues[$relationName][$index],
                                @$taintedFiles[$relationName][$index],
                                $widgetSchema[$relationName][$index],
                                $validatorSchema[$relationName][$index],
                                $form->embeddedForms[$relationName]->embeddedForms[$index]);
                    }
                }


            }
        }



    }


    public function bind(array $taintedValues = null, array $taintedFiles = null) {
        $this->cleanEmbedded($taintedValues, $taintedFiles);

        parent::bind($taintedValues, $taintedFiles);

    }

    /**
     * Saves embedded form objects.
     *
     * @param mixed $con   An optional connection object
     * @param array $forms An array of forms
     */
    public function saveEmbeddedForms($con = null, $forms = null) {
        if (null === $con) {
            $con = $this->getConnection();
        }

        if (null === $forms) {
            $forms = $this->embeddedForms;
        }

        foreach ($forms as $name => $form) {
            if ($form instanceof sfFormObject) {
                $form->saveEmbeddedForms($con);

                if ($form->getIsTranslationForm() == false) {

                    $form->getObject()->save($con);
                }
            } else {
                $this->saveEmbeddedForms($con, $form->getEmbeddedForms());
            }
        }
    }


    /**
     * @see sfFormObject
     */
    protected function doUpdateObject($values) {
        foreach ($values as $index => &$value) {
            if (array_key_exists($index, array_flip($this->_relations))) {
                if ($this->isI18n()) {
                    if (!array_key_exists($index, $this->_translations)) {
                        unset($values[$index]);
                    }
                } else {
                    foreach ($value as $subIndex => $subValue) {
                        if ($subIndex === 'new') {
                            unset($values[$index][$subIndex]);

                        } else {
                            unset($values[$index][$subIndex]);
                        }
                    }
                }

            } elseif (strpos($index, '_list') !== false) {
                $this->saveManyToMany($index, $value);
            }
        }

        $this->getObject()->fromArray($values);

    }

    /**
     * An alternative for the sfFormDoctrine Many list save class.
     * This tries to emulate it's behaviour.
     *
     * @param <type> $key
     * @param <type> $value
     */
    public function saveManyToMany($key, $value) {
        $relation = str_replace('_list', '', $key);

        $relationName = $this->camelize($relation);

        $existing = $this->object->$relationName->getPrimaryKeys();
        $values = $value;
        if (!is_array($values)) {
            $values = array();
        }

        $unlink = array_diff($existing, $values);
        if (count($unlink)) {
            $this->object->unlink($relationName, array_values($unlink));
        }

        $link = array_diff($values, $existing);
        if (count($link)) {
            $this->object->link($relationName, array_values($link));
        }
    }
}
