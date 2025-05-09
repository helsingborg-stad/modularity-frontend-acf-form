<?php

namespace ModularityFrontendForm\Module;

use WpService\Contracts\GetQueryVar;
use WpService\Contracts\GetPost;
use WpService\Contracts\AddAction;
use WpService\Contracts\IsWPError;
use WpService\Contracts\WpUpdatePost;

class FormSecurity
{
    public function __construct(
        private GetQueryVar&GetPost&AddAction&WpUpdatePost&IsWPError $wpService,
        private string $formIdQueryParam,
        private string $formTokenQueryParam     
    ) {
        $this->wpService->addAction(
            "acf/submit_form",
            [$this, 'hijackSaveFormRedirect'],
            10,
            3
        );
    }

    /**
     * Checks if the request needs a tokenized request.
     *
     * This method checks if the request needs a tokenized request by checking if the form ID query parameter is set.
     *
     * @return bool True if the request needs a tokenized request, false otherwise.
     */
    public function needsTokenizedRequest(): bool
    {
        if ($this->wpService->getQueryVar($this->formIdQueryParam, false)) {
            return true;
        }
        return false;
    }

    /**
     * Checks if the user has tokenized access.
     *
     * This method checks if the user has tokenized access by checking if the form edit token is valid.
     *
     * @return bool True if the user has tokenized access, false otherwise.
     */
    public function hasTokenizedAccess(): bool
    {
        $postId = $this->wpService->getQueryVar($this->formIdQueryParam, null); 
        $token  = $this->wpService->getQueryVar($this->formTokenQueryParam, null);

        if(!is_numeric($postId)) {
            return false;
        }

        if(!is_string($token)) {
            return false;
        }

        return $this->isValidFormEditToken(
            $postId,
            $token
        );
    }

    /**
     * Checks if the form edit token is valid.
     *
     * This method checks if the form edit token is valid by comparing the stored token with the provided token.
     *
     * @param int|null $postId The post ID.
     * @param string|null $token The form edit token.
     *
     * @return bool True if the form edit token is valid, false otherwise.
     */
    public function isValidFormEditToken(?int $postId, ?string $token): bool
    {
        if (is_null($token)) {
            return false;
        }
        return $this->getStoredFromEditToken($postId) === $token;
    }

    /**
     * Generates a form edit token.
     *
     * This method generates a form edit token by hashing the post ID, a random string, and the AUTH_KEY.
     *
     * @param int $postId The post ID.
     * @param int $length The length of the form edit token.
     *
     * @return string The generated form edit token.
     */
    public function generateFromEditToken(int $postId, int $length = 16): string
    {
        $hash = hash_hmac(
            'sha256', random_bytes($length),
            (defined('AUTH_KEY') ? AUTH_KEY : '') . $postId,
            true
        );

        $base64Hash     = base64_encode($hash);
        $urlSafeToken   = strtr(rtrim($base64Hash, '='), '+/', '-_');

        return substr($urlSafeToken, 0, $length);
    }

    /**
     * Saves the form edit token.
     *
     * This method saves the form edit token as a post password.
     *
     * @param int $postId The post ID.
     * @param mixed $post The post object.
     * @param bool $update Whether this is an existing post being updated.
     *
     * @return bool True if the form edit token was saved, false on failure, null if the token already exists.
     */
    public function saveFormEditToken($postId, $token): ?bool
    {
        if(is_null($this->getStoredFromEditToken($postId))) {
            $postUpdateResult = $this->wpService->wpUpdatePost(
                [   
                    'ID' => $postId, 
                    'post_password' => $token
                ]
            );

            if($this->wpService->isWpError($postUpdateResult)) {
                return false;
            }
            return true;
        }
        return null;
    }

    /**
     * Retrieves the stored form edit token.
     *
     * This method returns the stored form edit token by retrieving the value of the specified query parameter.
     *
     * @param int $postId The post ID.
     * @return string The stored form edit token.
     */
    private function getStoredFromEditToken($postId): ?string
    {
        return $this->wpService->getPost($postId)->post_password ?: null; 
    }

    /**
     * Hijacks the save form redirect.
     * Partially stolen from ACF.
     * https://github.com/AdvancedCustomFields/acf/blob/ac35ec186010b74ade15df9c86c3b4578d3acb36/includes/forms/form-front.php#L562
     *
     * This method hijacks the save form redirect by redirecting the user to the specified URL.
     *
     * @param array $form The form data.
     * @param int $postId The post ID.
     *
     * @return void
     */
    public function hijackSaveFormRedirect($form, $postId)
    {
        //Redirect to the specified URL
        if ($return = acf_maybe_get($form, 'return', false)) {

            //New token
            $token = $this->generateFromEditToken($postId);

            //Save token, if already exists false is returned
            $savedFormEditToken = $this->saveFormEditToken(
                $postId,
                $token
            );

            if ($savedFormEditToken === true) {
                //Remove %placeholders%
                $return = str_replace('%post_id%', $postId, $return);
                $return = str_replace('%post_url%', get_permalink($postId), $return);

                //Add token to url
                $return = add_query_arg(array(
                    $this->formTokenQueryParam => $token
                ), $return);

                // redirect
                wp_redirect($return);
                exit;
            }
        }
    }
}
