<?php namespace Framework;

use Framework\Helper\Feasible;
use Framework\Helper\FeasibleInterface;

class Test2 implements FeasibleInterface {
  use Feasible {
    execute as executeFeasible;
  }

  public function execute( $name, $argument = null ) {
    return $this->executeFeasible( $name, $argument );
  }

  protected function frameworkTestSimple( EventData $event, StorageInterface $data ) {
    if( $event->get( EventData::NAMESPACE_ARGUMENT . ':test' ) == 2 ) {
      $event->set( 'output', [ $event->isStopped() ? 'stopped' : '', $event->isPrevented() ? 'prevented' : '' ] );
    }
  }

  protected function frameworkTestAdvance( EventData $event, StorageInterface $data ) {
    $event->set( 'output', 2 );
  }
}
