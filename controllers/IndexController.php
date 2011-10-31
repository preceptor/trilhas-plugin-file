<?php
class File_IndexController extends Tri_Controller_Action
{
    public function init()
    {
        parent::init();
        $this->view->title = "File";
    }
    
    public function indexAction()
    {
        $session  = new Zend_Session_Namespace('data');
        $table    = new Tri_Db_Table('file');
        $page     = Zend_Filter::filterStatic($this->_getParam('page'), 'int');
        $query    = Zend_Filter::filterStatic($this->_getParam("q"), 'stripTags');
        $message  = $this->_hasParam('message');
        $select   = $table->select();

        $select->where('classroom_id = ?', $session->classroom_id);

        if ($query) {
            $select->where('UPPER(name) LIKE UPPER(?)', "%$query%");
        }
        
        if ($message) {
            $this->view->messages = array('Success');
            $this->getResponse()->prepend('messages', $this->view->render('message.phtml'));
        }
        
        $paginator = new Tri_Paginator($select, $page);
        $this->view->data = $paginator->getResult();
        $this->view->q = $query;
    }

    public function formAction()
    {
        $form = new File_Form_File();
        $this->view->form = $form;
    }

    public function saveAction()
    {
        $form  = new File_Form_File();
        $table = new Tri_Db_Table('file');
        $session = new Zend_Session_Namespace('data');
        $data  = $this->_getAllParams();
        $this->_helper->layout->disableLayout();

        if ($form->isValid($data)) {
            if (!$form->location->receive()) {
                $this->_helper->_flashMessenger->addMessage('File fail');
            }
            
            $data = $form->getValues();
            $data['user_id']      = Zend_Auth::getInstance()->getIdentity()->id;
            $data['classroom_id'] = $session->classroom_id;

            $row = $table->createRow($data);
            $id = $row->save();
            Application_Model_Timeline::save('saved a new file', $data['name']);
        } else {
            $this->view->message = $this->view->translate('Error');
        }
    }

    public function deleteAction()
    {
        $table    = new Tri_Db_Table('file');
        $id       = Zend_Filter::filterStatic($this->_getParam('id'), 'int');
        $location = $this->_getParam('location');

        @unlink(APPLICATION_PATH . '/../data/upload/' . $location);

        if ($id) {
            $table->delete(array('id = ?' => $id));
            $this->_helper->_flashMessenger->addMessage('Success');
        }

        $this->_redirect('file/index/');
    }
}