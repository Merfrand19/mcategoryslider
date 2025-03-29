<?php

    if( !defined('_PS_VERSION_') ){
        exit;
    }
    class MCategorySlider extends Module {
        public function __construct() {
            $this->name = 'mcategoryslider';
            $this->tab = 'front_office_features';
            $this->version = '1.0.0';
            $this->author = 'Merfrand';
            $this->need_instance = 0;
            $this->ps_versions_compliancy =[
                'min' => '1.7',
                'max' => '8.2.0',
            ];
            $this->bootstrap = true;
            parent::__construct();
            $this->confirmUninstall = $this->l('Are you sure you want to uninstall the Category Slider module?');
     
            $this->displayName = $this->l('Category Slider');
            $this->description = $this->l('Displays a category slider.');
        }

        public function install() {
            return parent::install() 
                && $this->registerHook('displayHome')
                && $this->registerHook('actionFrontControllerSetMedia'); ;
        }

        public function uninstall()
        {
            $num_categories = (int) Configuration::get('MCATEGORIES_NUMBER');
            Configuration::deleteByName('MCATEGORIES_NUMBER');
            for ($i = 1; $i <= $num_categories; $i++) {
                Configuration::deleteByName('CATEGORY_IMAGE_' . $i);
            }
            $upload_dir = _PS_MODULE_DIR_ . 'mcategoryslider/images/';
            for ($i = 1; $i <= $num_categories; $i++) {
                $image_path = $upload_dir . 'category_' . $i . '.jpg';
                if (file_exists($image_path)) {
                    unlink($image_path); // Supprime l'image si elle existe
                }
            }
            return parent::uninstall();
        }

        public function getContent()
        {
            $output = '';
        
            // Étape 1 : Sauvegarder le nombre de catégories
            if (Tools::isSubmit('submit_categories_number')) {
                $num_categories = (int)Tools::getValue('MCATEGORIES_NUMBER');
                Configuration::updateValue('MCATEGORIES_NUMBER', $num_categories);
                $output .= $this->displayConfirmation($this->l('Number of categories saved successfully.'));
            }
        
            // Étape 2 : Sauvegarder les images des catégories
            if (Tools::isSubmit('submit_mcategoryslider')) {
                $num_categories = (int)Configuration::get('MCATEGORIES_NUMBER');
        
                for ($i = 1; $i <= $num_categories; $i++) {
                    if (isset($_FILES['category_image_' . $i]) && isset($_FILES['category_image_' . $i]['tmp_name'])) {
                        $image_tmp = $_FILES['category_image_' . $i]['tmp_name'];
                        $image_name = $_FILES['category_image_' . $i]['name'];
                        $image_ext = pathinfo($image_name, PATHINFO_EXTENSION);
                        $upload_dir = _PS_MODULE_DIR_ . 'mcategoryslider/images/';
        
                        if (in_array($image_ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                            $image_path = $upload_dir . 'category_' . $i . '.' . $image_ext;
                            move_uploaded_file($image_tmp, $image_path);
                            Configuration::updateValue('CATEGORY_IMAGE_' . $i, $image_path);
                        }
                    }
                }
        
                $output .= $this->displayConfirmation($this->l('Configuration saved successfully.'));
            }
        
            // Formulaire Étape 1 : Choix du nombre de catégories
            $output .= '
            <form action="' . $_SERVER['REQUEST_URI'] . '" method="post">
                <label for="MCATEGORIES_NUMBER">' . $this->l('Number of categories to display:') . '</label>
                <input type="number" name="MCATEGORIES_NUMBER" value="' . (int)Configuration::get('MCATEGORIES_NUMBER') . '" min="1" required />
                <button type="submit" name="submit_categories_number" class="button">' . $this->l('Save') . '</button>
            </form>
            <br/>';
        
            // Récupérer le nombre de catégories défini
            $num_categories = (int)Configuration::get('MCATEGORIES_NUMBER');
        
            if ($num_categories > 0) {
                $output .= '<form action="' . $_SERVER['REQUEST_URI'] . '" method="post" enctype="multipart/form-data">';
                
                // Récupérer les catégories disponibles
                $categories = Category::getSimpleCategories($this->context->language->id);
                
                for ($i = 1; $i <= $num_categories; $i++) {
                    $categoryName = isset($categories[$i]) ? $categories[$i]['name'] : $this->l('Category') . ' ' . $i;
        
                    $output .= '
                    <label>' . $this->l('Image for') . ' ' . htmlspecialchars($categoryName) . ':</label>
                    <input type="file" name="category_image_' . $i . '" />
                    <br/><br/>';
                }
        
                $output .= '<button type="submit" name="submit_mcategoryslider" class="button">' . $this->l('Save configuration') . '</button>
                </form>';
            }
        
            return $output;
        }
        

        public function hookDisplayHome($params)
        {
            $categories = [];
            $id_lang = (int)Context::getContext()->language->id;
            $categoryObjects = Category::getCategories($id_lang, true, false);

            // Définir le nombre de catégories à afficher depuis la configuration
            $num_categories = (int)Configuration::get('MCATEGORIES_NUMBER');

            // Définir le chemin de l'image par défaut
            $defaultImage = $this->_path . 'images/default_img.jpg'; // Image par défaut

            $count = 0; // Compteur pour limiter le nombre de catégories affichées

            foreach ($categoryObjects as $category) {
                if (!isset($category['id_category']) || !isset($category['name'])) {
                    continue;
                }

                // Exclure les catégories "Racine" (ID 1) et "Accueil" (ID 2)
                if ($category['id_category'] == 1 || $category['id_category'] == 2) {
                    continue;
                }

                $categoryId = (int) $category['id_category'];
                $categoryParentId = (int) $category['id_parent'];
                $categoryLink = Context::getContext()->link->getCategoryLink($categoryId);

                // Récupérer l'image depuis la configuration
                $imagePath = Configuration::get('CATEGORY_IMAGE_' . $count + 1);

                if ($imagePath) {
                    $imagePath = preg_replace('/^.*\/modules\//', '/modules/', $imagePath);
                } else {
                    $imagePath = $defaultImage;
                }

                // Récupérer le nom du parent
                $parentName = 'Root'; // Valeur par défaut pour les catégories sans parent
                if ($categoryParentId > 0) {
                    $parentCategory = new Category($categoryParentId, $id_lang);
                    if (Validate::isLoadedObject($parentCategory)) {
                        $parentName = $parentCategory->name;
                    }
                }

                // Ajouter la catégorie au tableau
                $categories[] = [
                    'name' => $category['name'],
                    'link' => $categoryLink,
                    'image' => $imagePath,
                    'parent' => $parentName,
                ];

                $count++;

                // Stopper la boucle si le nombre max de catégories est atteint
                if ($count >= $num_categories) {
                    break;
                }
            }

            // Assigner les catégories à Smarty
            $this->context->smarty->assign('categories', $categories);

            // Retourner le rendu du template
            return $this->display(__FILE__, 'mcategoryslider.tpl');
        }





        public function hookActionFrontControllerSetMedia($params)
        {
            $this->context->controller->registerStylesheet(
            'mcategoryslider-style',
            $this->_path.'views/css/mcategoryslider.css',
            [
            'media' => 'all',
            'priority' => 999,
            ]);

            $this->context->controller->registerStylesheet(
                'mcategoryslider-swiper',
                'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css',
                [
                    'media' => 'all',
                    'priority' => 999,
                ]
            );

            $this->context->controller->registerJavascript(
                'mcategoryslider-swiper',
                'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js',
                [
                    'position' => 'bottom',
                    'priority' => 999,
                ]
            );

            $this->context->controller->registerJavascript(
                'mcategoryslider-init',
                $this->_path . 'views/js/mcategoryslider.js',
                [
                    'position' => 'bottom',
                    'priority' => 999,
                ]
            );
        }
    }
?>