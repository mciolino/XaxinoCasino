<?php

namespace App\Services;

use Web3\Web3;
use Web3\Contract;
use Web3\Utils;
use Web3\Providers\HttpProvider;
use Web3\RequestManagers\HttpRequestManager;
use Illuminate\Support\Facades\Log;
use Exception;

class OnChainPayoutService
{
    protected $web3;
    protected $contract;
    protected $contractAddress;
    protected $ownerAddress;
    protected $ownerPrivateKey;

    /**
     * Create a new service instance.
     */
    public function __construct()
    {
        $provider = env('ETH_PROVIDER', 'https://sepolia.infura.io/v3/your_infura_id');
        $this->web3 = new Web3(new HttpProvider(new HttpRequestManager($provider)));
        $this->contractAddress = env('ETH_CONTRACT_ADDRESS', '');
        $this->ownerAddress = env('ETH_OWNER_ADDRESS', '');
        $this->ownerPrivateKey = env('ETH_OWNER_PRIVATE_KEY', '');
        
        // Load contract ABI
        $abiPath = storage_path('app/contracts/CasinoPayout.json');
        
        if (file_exists($abiPath)) {
            $abi = json_decode(file_get_contents($abiPath), true);
            $this->contract = new Contract($this->web3->provider, $abi);
        } else {
            Log::error('OnChainPayoutService: Contract ABI file not found at ' . $abiPath);
        }
    }

    /**
     * Send an on-chain payout to a winner.
     *
     * @param string $winnerAddress
     * @param float $amountEth
     * @return string Transaction hash
     * @throws Exception
     */
    public function payout($winnerAddress, $amountEth)
    {
        if (!$this->contract) {
            throw new Exception('Contract not initialized');
        }
        
        if (!$this->ownerPrivateKey || !$this->ownerAddress || !$this->contractAddress) {
            throw new Exception('Missing Ethereum configuration');
        }
        
        try {
            // Convert ETH amount to Wei
            $amountWei = Utils::toWei($amountEth, 'ether');
            
            // Prepare the function call data
            $data = $this->contract->at($this->contractAddress)
                ->getData('payout', [$winnerAddress, $amountWei]);
            
            // Build transaction parameters
            $transaction = [
                'from' => $this->ownerAddress,
                'to' => $this->contractAddress,
                'data' => $data,
                'gas' => '0x' . dechex(200000), // Adjust gas as needed
                'gasPrice' => '0x' . dechex(Utils::toWei('20', 'gwei')),
            ];
            
            // Get nonce for the transaction
            $nonce = null;
            $this->web3->eth->getTransactionCount($this->ownerAddress, 'pending', function ($err, $count) use (&$nonce) {
                if ($err) {
                    throw new Exception('Failed to get nonce: ' . $err->getMessage());
                }
                $nonce = $count;
            });
            
            $transaction['nonce'] = '0x' . dechex($nonce);
            
            // Sign the transaction
            $signedTx = $this->signTransaction($transaction);
            
            // Send the transaction
            $txHash = null;
            $this->web3->eth->sendRawTransaction('0x' . $signedTx, function ($err, $hash) use (&$txHash) {
                if ($err) {
                    throw new Exception('Failed to send transaction: ' . $err->getMessage());
                }
                $txHash = $hash;
            });
            
            Log::info("Payout transaction sent: {$txHash} to {$winnerAddress} for {$amountEth} ETH");
            
            return $txHash;
        } catch (Exception $e) {
            Log::error('OnChainPayoutService: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Sign an Ethereum transaction.
     * 
     * Note: In a real implementation, you would use a proper signing library like eth-tx
     * This is a simplified example.
     *
     * @param array $transaction
     * @return string
     */
    protected function signTransaction($transaction)
    {
        // In a real implementation, this would use a proper Ethereum transaction
        // signing library to sign with the private key.
        
        // This is just a placeholder that returns a simulated signed transaction
        $txHex = '0f8aa8...'; // Simulated signed transaction hex
        
        return $txHex;
    }
}
