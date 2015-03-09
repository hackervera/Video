<?php

    namespace IdnoPlugins\Video {

        class Video extends \Idno\Common\Entity
        {

            function getTitle()
            {
                if (empty($this->title)) return 'Untitled';

                return $this->title;
            }

            function getDescription()
            {
                if (!empty($this->body)) return $this->body;

                return '';
            }

            function getURL()
            {
                // If we have a URL override, use it
                if (!empty($this->url)) {
                    return $this->url;
                }

                if (!empty($this->canonical)) {
                    return $this->canonical;
                }
                if (($this->getID())) {
                    return \Idno\Core\site()->config()->url . 'video/' . $this->getID() . '/' . $this->getPrettyURLTitle();
                } else {
                    return parent::getURL();
                }
            }

            /**
             * Entry objects have type 'article'
             * @return 'article'
             */
            function getActivityStreamsObjectType()
            {
                return 'article';
            }

            function saveDataFromInput()
            {

                if (empty($this->_id)) {
                    $new = true;
                } else {
                    $new = false;
                }

                if ($new) {
                    if (!\Idno\Core\site()->triggerEvent("file/upload",[],true)) {
                        return false;
                    }
                }

                $body = \Idno\Core\site()->currentPage()->getInput('body');
                if (!empty($_FILES['video']['tmp_name']) || !empty($this->_id)) {
                    $this->body        = $body;
                    $this->title       = \Idno\Core\site()->currentPage()->getInput('title');
                    $this->description = \Idno\Core\site()->currentPage()->getInput('description');

                    if ($time = \Idno\Core\site()->currentPage()->getInput('created')) {
                        if ($time = strtotime($time)) {
                            $this->created = $time;
                        }
                    }

                    if (!empty($_FILES['video']['tmp_name'])) {
                        if (\Idno\Entities\File::isImage($_FILES['video']['tmp_name'])) {
                            if ($size = getimagesize($_FILES['video']['tmp_name'])) {
                                $this->width  = $size[0];
                                $this->height = $size[1];
                            }
                            if ($video = \Idno\Entities\File::createFromFile($_FILES['video']['tmp_name'], $_FILES['video']['name'], $_FILES['video']['type'], true)) {
                                $this->attachFile($video);
                            }
                        }
                    }
                    $this->setAccess('PUBLIC');
                    if ($this->save($new)) {

                        \Idno\Core\Webmention::pingMentions($this->getURL(), \Idno\Core\site()->template()->parseURLs($this->getDescription()));

                        return true;
                    }
                } else {
                    \Idno\Core\site()->session()->addErrorMessage('You can\'t save an empty video.');
                }

                return false;

            }

            function deleteData()
            {
                \Idno\Core\Webmention::pingMentions($this->getURL(), \Idno\Core\site()->template()->parseURLs($this->getDescription()));
            }

        }

    }