<?php

namespace SimpleJWTLogin\Helpers;

class ViewLoader
{
    /**
     * @var string
     */
    private $basePath;

    public function __construct($basePath)
    {
        $this->basePath = rtrim($basePath, '/') . '/';
    }

    /**
     * Render a view file directly to output.
     *
     * @param string $view Filename relative to base path, or absolute path.
     * @param array  $data Variables to make available inside the view.
     */
    public function render($view, array $data = array())
    {
        $path = $this->resolvePath($view);
        if (!file_exists($path)) {
            return;
        }
        $this->doInclude($path, $data);
    }

    /**
     * Render a view file and return its output as a string.
     *
     * @param string $view Filename relative to base path, or absolute path.
     * @param array  $data Variables to make available inside the view.
     *
     * @return string
     */
    public function fetch($view, array $data = array())
    {
        $path = $this->resolvePath($view);
        if (!file_exists($path)) {
            return '';
        }
        ob_start();
        $this->doInclude($path, $data);
        $output = ob_get_clean();
        return ($output !== false) ? $output : '';
    }

    private function resolvePath($view)
    {
        if (strpos($view, '/') === 0) {
            return $view;
        }
        return $this->basePath . $view;
    }

    private function doInclude($viewPath, array $viewVars)
    {
        extract($viewVars, EXTR_SKIP);
        include $viewPath;
    }
}
