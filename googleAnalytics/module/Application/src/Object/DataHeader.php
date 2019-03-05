<?php
namespace Application\Object;

class DataHeader {
    private $menu_id;
    private $menu_parent;
    private $menu_name;
    private $menu_order;
    private $menu_pos;
    private $is_public;
    private $url;
    private $icon_class;
    private $module;
    private $menu_properties;
    private $state;
    private $path;

    /**
     * DataHeader constructor.
     * @param $menu_id
     * @param $menu_parent
     * @param $menu_name
     * @param $menu_order
     * @param $menu_pos
     * @param $is_public
     * @param $url
     * @param $icon_class
     * @param $module
     * @param $menu_properties
     * @param $state
     * @param $path
     */
    public function __construct()
    {

    }


    /**
     * @return mixed
     */
    public function getMenuId()
    {
        return $this->menu_id;
    }

    /**
     * @param mixed $menu_id
     */
    public function setMenuId($menu_id)
    {
        $this->menu_id = $menu_id;
    }

    /**
     * @return mixed
     */
    public function getMenuParent()
    {
        return $this->menu_parent;
    }

    /**
     * @param mixed $menu_parent
     */
    public function setMenuParent($menu_parent)
    {
        $this->menu_parent = $menu_parent;
    }

    /**
     * @return mixed
     */
    public function getMenuName()
    {
        return $this->menu_name;
    }

    /**
     * @param mixed $menu_name
     */
    public function setMenuName($menu_name)
    {
        $this->menu_name = $menu_name;
    }

    /**
     * @return mixed
     */
    public function getMenuOrder()
    {
        return $this->menu_order;
    }

    /**
     * @param mixed $menu_order
     */
    public function setMenuOrder($menu_order)
    {
        $this->menu_order = $menu_order;
    }

    /**
     * @return mixed
     */
    public function getMenuPos()
    {
        return $this->menu_pos;
    }

    /**
     * @param mixed $menu_pos
     */
    public function setMenuPos($menu_pos)
    {
        $this->menu_pos = $menu_pos;
    }

    /**
     * @return mixed
     */
    public function getisPublic()
    {
        return $this->is_public;
    }

    /**
     * @param mixed $is_public
     */
    public function setIsPublic($is_public)
    {
        $this->is_public = $is_public;
    }

    /**
     * @return mixed
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param mixed $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * @return mixed
     */
    public function getIconClass()
    {
        return $this->icon_class;
    }

    /**
     * @param mixed $icon_class
     */
    public function setIconClass($icon_class)
    {
        $this->icon_class = $icon_class;
    }

    /**
     * @return mixed
     */
    public function getModule()
    {
        return $this->module;
    }

    /**
     * @param mixed $module
     */
    public function setModule($module)
    {
        $this->module = $module;
    }

    /**
     * @return mixed
     */
    public function getMenuProperties()
    {
        return $this->menu_properties;
    }

    /**
     * @param mixed $menu_properties
     */
    public function setMenuProperties($menu_properties)
    {
        $this->menu_properties = $menu_properties;
    }

    /**
     * @return mixed
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param mixed $state
     */
    public function setState($state)
    {
        $this->state = $state;
    }

    /**
     * @return mixed
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param mixed $path
     */
    public function setPath($path)
    {
        $this->path = $path;
    }


}