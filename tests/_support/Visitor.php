<?php


/**
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method \Codeception\Lib\Friend haveFriend($name, $actorClass = NULL)
 *
 * @SuppressWarnings(PHPMD)
*/
class Visitor extends \Codeception\Actor
{
    use _generated\VisitorActions;

   /**
    * Define custom actions here
    */
  	public function fillAtkField($field,$value){
      $i = $this;
      $i->fillField('[data-shortname='.$field.']',$value);
    }
    
    public function waitForPageLoad(){
      $i=$this;
      $i->WaitPageLoad();
    }

    public function login($user,$pwd){      
      $i=$this;
      $i->fillAtkField('username',$user);
      $i->fillAtkField('password',$pwd);
      $i->click(['css'=>'button[type=submit]']);
      $i->waitPageLoad();
    }
}
