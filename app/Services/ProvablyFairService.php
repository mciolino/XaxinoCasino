<?php

namespace App\Services;

class ProvablyFairService
{
    private $serverSeed;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->generateNewServerSeed();
    }
    
    /**
     * Generate a new server seed
     * 
     * @return string
     */
    public function generateNewServerSeed(): string
    {
        // Generate a random server seed (32 bytes)
        $this->serverSeed = bin2hex(random_bytes(32));
        return $this->serverSeed;
    }
    
    /**
     * Get the current server seed
     * 
     * @return string
     */
    public function getServerSeed(): string
    {
        return $this->serverSeed;
    }
    
    /**
     * Generate a hash of the server seed for provably fair verification
     * 
     * @return string
     */
    public function generateServerSeedHash(): string
    {
        return hash('sha256', $this->serverSeed);
    }
    
    /**
     * Calculate a roll result using server seed and client seed
     * 
     * @param string $serverSeed
     * @param string $clientSeed
     * @return float
     */
    public function calculateRoll(string $serverSeed, string $clientSeed): float
    {
        // Combine server seed and client seed
        $combined = hash('sha256', $serverSeed . $clientSeed);
        
        // Extract the first 8 characters of the hash and convert to number between 0-100
        $roll = hexdec(substr($combined, 0, 8)) % 10000 / 100;
        
        return $roll;
    }
    
    /**
     * Verify a roll result
     * 
     * @param string $serverSeed
     * @param string $clientSeed
     * @param float $storedRoll
     * @return bool
     */
    public function verifyRoll(string $serverSeed, string $clientSeed, float $storedRoll): bool
    {
        $calculatedRoll = $this->calculateRoll($serverSeed, $clientSeed);
        
        // Use a small epsilon for float comparison
        return abs($calculatedRoll - $storedRoll) < 0.00001;
    }
}
