<?php
namespace jcabanillas\conekta;

require_once("lib/Conekta.php");

class MyConekta
{

    public $apiKey = "key_v7Tu9yww6KSfd5fzZrpJzQ";

    function __construct()
    {
        \Conekta::setApiKey($this->apiKey);
    }

    //creating customer
    public function CreateCustomer($name, $email)
    {
        try {
            $customer = \Conekta_Customer::create(
                array(
                    'name' => $name,
                    'email' => $email,
                    //'phone' => "55-5555-5555",
                    // 'cards' => array("tok_8kZwafM8IcN23Nd9"),
                    //'plan'  => "gold-plan"
                )
            );
            return $this->Response(1, $customer);
        } catch (\Conekta_Error $e) {
            return $this->Response(0, $e);

        }
    }

    //find customer
    public function FindCustomer($cid)
    {
        try {
            $customer = \Conekta_Customer::find($cid);
            return $this->Response(1, $customer);

        } catch (\Conekta_Error $e) {
            return $this->Response(0, $e);
        }
    }

    //creating card
    public function CreateCard($cid, $token)
    {
        $customer = $this->FindCustomer($cid);

        if ($customer['type'] == 1) {


            try {
                $card = $customer['data']->createCard(array('token' => $token));
                return $this->Response(1, $card);
            } catch (\Conekta_Error $e) {
                return $this->Response(0, $e);
            }
        } else {
            return $this->Response(0, $customer['data']);
        }
    }

    //geting user cards
    public function GetUserCard($cid)
    {
        $customer = $this->FindCustomer($cid);
        if ($customer['type'] == 1) {
            return $this->Response(1, $customer['data']);
        } else {
            return $this->Response(0, $customer['data']);
        }
    }

    //update card
    public function UpdateDefaultCard($cid, $card)
    {
        $customer = $this->FindCustomer($cid);

        if ($customer['type'] == 1) {
            try {
                $customer = $customer['data']->update(
                    array(

                        'default_card_id' => $card
                    )
                );
                return $this->Response(1, $customer);

            } catch (\Conekta_Error $e) {
                return $this->Response(0, $e);

            }
        } else {
            return $this->Response(0, $customer['data']);

        }
    }

    public function DeleteCard($cid, $index)
    {

        $customer = $this->FindCustomer($cid);
        //print_r($customer['data']); exit;
        if ($customer['type'] == 1) {
            try {
                $card = $customer['data']->cards[$index]->delete();

                return $this->Response(1, $card);
            } catch (\Conekta_Error $e) {
                return $this->Response(0, $e);
            }

        } else {
            return $this->Response(0, $customer['data']);
        }
    }

    //Function to validate if the token does exist
    public static function check_token($token)
    {
        if ($token == $_SESSION['token'])
            return true;

        return false;
    }

    //Function to generate a md5 32digits token
    public static function tokengenerator($len = 32)
    {
        //seed
        $keychars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        // RANDOM KEY GENERATOR
        $id = "";
        $max = strlen($keychars) - 1;

        for ($i = 0; $i < $len; $i++)
            $id .= substr($keychars, rand(0, $max), 1);

        return md5($id);
    }

    //Function to make payments in Bank
    public static function bank($amount, $name, $email, $phone, $bank)
    {
        $request = array(
            'amount' => $amount,
            'currency' => self::$currency,
            'description' => self::$description,
            'details' => array(
                'name' => $name,
                'email' => $email,
                'phone' => $phone
            ),
            'bank' => array('type' => $bank)
        );

        try {
            $response = \Conekta_Charge::create($request);

            echo
                'status=' . $response['status'] .
                '&currency=' . $response['currency'] .
                '&description=' . $response['description'] .
                '&amount=' . $response['amount'] .
                '&service_name=' . $response['payment_method']['service_name'] .
                '&service_number=' . $response['payment_method']['service_number'] .
                '&reference=' . $response['payment_method']['reference'] .
                '&type=' . $response['payment_method']['type'] .
                '&expiry_date=' . date('d/m/Y') .
                '&token=' . $_SESSION['token'];

        } catch (Exception $e) {
            // Catch all exceptions including validation errors.
            echo $e->getMessage();
        }
    }

    //Function to make payments in Oxxo stores
    public static function oxxo($amount, $email)
    {

        $request = array(
            'amount' => $amount,
            'currency' => self::$currency,
            'description' => self::$description,
            'details' => array('email' => $email),
            'cash' => array('type' => 'oxxo')
        );
        try {
            $response = \Conekta_Charge::create($request);

            echo
                'status=' . $response['status'] .
                '&currency=' . $response['currency'] .
                '&description=' . $response['description'] .
                '&amount=' . $response['amount'] .
                '&expiry_date=' . $response['payment_method']['expiry_date'] .
                '&barcode=' . $response['payment_method']['barcode'] .
                '&barcode_url=' . $response['payment_method']['barcode_url'] .
                '&type=' . $response['payment_method']['type'] .
                '&email=' . $email .
                '&token=' . $_SESSION['token'];

        } catch (Exception $e) {
            // Catch all exceptions including validation errors.
            echo $e->getMessage();
        }
    }

    //Function to make payments with a Credit/Debit Card
    public function card($paymentData)
    {
        try {
            $charge = \Conekta_Charge::create(array(
                'description' => $paymentData['description'],
                'reference_id' => $paymentData['reference_id'],
                'amount' => $paymentData['amount'],
                'currency' => $paymentData['currency'],
                'details' => $paymentData['details'],
                'card' => $paymentData['card'],
                'details' => $paymentData['details']
            ));
            return true;
        } catch (\Conekta_Error $e) {
            return false;
        }
    }

    private function Response($success, $result)
    {
        if ($success == 0) {
            $result = $result->getMessage();
        }
        return array("type" => $success, "data" => $result);
    }
    //END OF CLASS
}