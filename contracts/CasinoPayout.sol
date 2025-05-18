// SPDX-License-Identifier: MIT
pragma solidity ^0.8.0;

/**
 * @title CasinoPayout
 * @dev Contract for handling casino payouts to winners
 */
contract CasinoPayout {
    address public owner;
    
    event PayoutSent(address indexed recipient, uint256 amount, uint256 timestamp);
    
    /**
     * @dev Constructor sets the owner to the deployer of the contract
     */
    constructor() {
        owner = msg.sender;
    }
    
    /**
     * @dev Modifier to restrict functions to the owner
     */
    modifier onlyOwner() {
        require(msg.sender == owner, "Only the owner can call this function");
        _;
    }
    
    /**
     * @dev Function to change the owner of the contract
     * @param newOwner The address of the new owner
     */
    function transferOwnership(address newOwner) external onlyOwner {
        require(newOwner != address(0), "New owner cannot be the zero address");
        owner = newOwner;
    }
    
    /**
     * @dev Send a payout to a winner
     * @param winner The address of the winner receiving the payout
     * @param amount The amount to pay out in wei
     */
    function payout(address payable winner, uint256 amount) external onlyOwner {
        require(address(this).balance >= amount, "Insufficient contract balance");
        
        (bool success, ) = winner.call{value: amount}("");
        require(success, "Transfer failed");
        
        emit PayoutSent(winner, amount, block.timestamp);
    }
    
    /**
     * @dev Get the contract balance
     * @return The balance of the contract in wei
     */
    function getBalance() external view returns (uint256) {
        return address(this).balance;
    }
    
    /**
     * @dev Allow the contract to receive ETH
     */
    receive() external payable {}
    
    /**
     * @dev Fallback function for compatibility
     */
    fallback() external payable {}
}
