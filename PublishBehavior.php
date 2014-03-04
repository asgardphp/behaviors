<?php
namespace Asgard\Behaviors;

class PublishBehavior implements \Asgard\Core\Behavior {
	public static function load($entityDefinition, $params=null) {
		$entityName = $entityDefinition->getClass();

		$entityDefinition->addProperty('published', array('type'=>'boolean', 'default'=>true));

		#Article::published()
		$entityDefinition->addStaticMethod('published', function() use($entityName) {
			return $entityName::orm()->where(array('published'=>1));
		});
		#Article::loadPublished(2)
		$entityDefinition->addStaticMethod('loadPublished', function($id) use($entityName) {
			return $entityName::published()->where(array('id'=>$id))->first();
		});

		$entityDefinition->hook('asgardadmin', function($chain, $admin_controller) use($entityName) {
			try {
				$admin_controller::addHook(array(
					'route'			=>	':id/publish',
					'name'			=>	'asgard_'.$entityName.'_publish',
					'controller'	=>	'PublishBehavior',
					'action'		=>	'publish'
				));
			} catch(\Exception $e) {} #if the admincontroller does not exist for this Entity
		});

		$entityDefinition->hook('asgardadmin_actions', function($chain, $entity) use($entityName) {
				return '<a href="'.\Asgard\Core\App::get('url')->url_for('asgard_'.$entityName.'_publish', array('id' => $entity->id), false).'">'.($entity->published ? __('Unpublish'):__('Publish')).'</a> | ';
		});

		$entityDefinition->hook('asgardadmin_globalactions', function($chain, &$actions) use($entityName) {
			#publish
			$actions[] = array(
				'text'	=>	__('Publish'),
				'value'	=>	'publish',
				'callback'	=>	function() use($entityName) {
					if(POST::size() > 1) {
						foreach(POST::get('id') as $id) {
							$entity = $entityName::load($id);
							$entity->save(array('published'=>1));
						}
					
						Flash::addSuccess(sprintf(__('%s element(s) published with success!'), sizeof(POST::get('id'))));
					}
				}
			);
			#unpublish
			$actions[] = array(
				'text'	=>	__('Unpublish'),
				'value'	=>	'unpublish',
				'callback'	=>	function() use($entityName) {
					if(POST::size()>1) {
						foreach(POST::get('id') as $id) {
							$entity = $entityName::load($id);
							$entity->save(array('published'=>0));
						}
					
						Flash::addSuccess(sprintf(__('%s element(s) unpublished with success!'), sizeof(POST::get('id'))));
					}
				}
			);
		});
	}
}