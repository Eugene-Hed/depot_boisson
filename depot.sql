-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : localhost:3306
-- Généré le : ven. 29 nov. 2024 à 23:35
-- Version du serveur : 8.0.30
-- Version de PHP : 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `depot`
--

-- --------------------------------------------------------

--
-- Structure de la table `categorie`
--

CREATE TABLE `categorie` (
  `id_categorie` int NOT NULL,
  `nom` varchar(255) NOT NULL,
  `description` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `categorie`
--

INSERT INTO `categorie` (`id_categorie`, `nom`, `description`) VALUES
(1, 'Eaux', 'Eaux minérales naturelles, gazeuses ou plates'),
(2, 'Sodas', 'Boissons gazeuses sucrées comme cola, limonade'),
(3, 'Jus de Fruits', 'Jus naturels ou industriels à base de fruits'),
(4, 'Bières', 'Boissons alcoolisées issues de la fermentation de céréales'),
(5, 'Vins', 'Boissons alcoolisées issues de la fermentation de raisins'),
(6, 'Spiritueux', 'Alcools forts comme whisky, vodka, rhum, gin'),
(7, 'Boissons Chaudes', 'Cafés, thés, chocolats chauds, etc.'),
(8, 'Énergisants', 'Boissons pour revitaliser comme Red Bull, Monster'),
(9, 'Cocktails', 'Mélanges de plusieurs boissons, alcoolisées ou non'),
(10, 'Boissons Lactées', 'Laits aromatisés, yaourts à boire, etc.');

-- --------------------------------------------------------

--
-- Structure de la table `client`
--

CREATE TABLE `client` (
  `id_client` int NOT NULL,
  `nom` varchar(255) NOT NULL,
  `adresse` text NOT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `id_utilisateur` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `client`
--

INSERT INTO `client` (`id_client`, `nom`, `adresse`, `telephone`, `email`, `mot_de_passe`, `id_utilisateur`) VALUES
(1, 'Meka Kengne Pascale Ariel', 'Non renseignée', '656774288', 'mekapascale2006@gmail.com', '$2y$10$cpoiEesFiA/PYPwa4IxsGuFgnR5NfX0bShEs1qDWLhrI/jOFUU7Ou', 5);

-- --------------------------------------------------------

--
-- Structure de la table `commandeclient`
--

CREATE TABLE `commandeclient` (
  `id_commande` int NOT NULL,
  `id_client` int NOT NULL,
  `date_commande` date NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `statut` enum('En attente','En préparation','Livrée','Annulée') DEFAULT 'En attente',
  `id_utilisateur` int DEFAULT NULL,
  `id_depot` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `commandeclient`
--

INSERT INTO `commandeclient` (`id_commande`, `id_client`, `date_commande`, `total`, `statut`, `id_utilisateur`, `id_depot`) VALUES
(1, 1, '2024-11-29', 22500.00, 'Livrée', 5, 1),
(2, 1, '2024-11-30', 45000.00, 'Livrée', 1, 1);

-- --------------------------------------------------------

--
-- Structure de la table `commandefournisseur`
--

CREATE TABLE `commandefournisseur` (
  `id_commande` int NOT NULL,
  `id_fournisseur` int NOT NULL,
  `date_commande` date NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `statut` enum('En attente','Reçue','Annulée') DEFAULT 'En attente',
  `id_depot` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `commandefournisseur`
--

INSERT INTO `commandefournisseur` (`id_commande`, `id_fournisseur`, `date_commande`, `total`, `statut`, `id_depot`) VALUES
(1, 1, '2024-11-30', 900000.00, 'Reçue', 1);

-- --------------------------------------------------------

--
-- Structure de la table `depots`
--

CREATE TABLE `depots` (
  `id` int NOT NULL,
  `nom` varchar(100) NOT NULL,
  `adresse` text NOT NULL,
  `contact` varchar(50) DEFAULT NULL,
  `id_proprietaire` int NOT NULL,
  `logo` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `depots`
--

INSERT INTO `depots` (`id`, `nom`, `adresse`, `contact`, `id_proprietaire`, `logo`) VALUES
(1, 'A&amp;amp;H', '                                MFOU                            ', '692042589', 1, 'uploads/logos/674a0a81619ea.png'),
(2, 'vin-sarl', 'Yaounde, mimboman, DOVV OPEP', '656774288', 1, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `detailscommandeclient`
--

CREATE TABLE `detailscommandeclient` (
  `id_details_commande` int NOT NULL,
  `id_commande` int NOT NULL,
  `id_produit` int NOT NULL,
  `quantite` int NOT NULL,
  `prix_unit` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `detailscommandeclient`
--

INSERT INTO `detailscommandeclient` (`id_details_commande`, `id_commande`, `id_produit`, `quantite`, `prix_unit`) VALUES
(1, 1, 2, 50, 450.00),
(2, 2, 2, 100, 450.00);

-- --------------------------------------------------------

--
-- Structure de la table `detailscommandefournisseur`
--

CREATE TABLE `detailscommandefournisseur` (
  `id_details_commande` int NOT NULL,
  `id_commande` int NOT NULL,
  `id_produit` int NOT NULL,
  `quantite` int NOT NULL,
  `prix_unit` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `detailscommandefournisseur`
--

INSERT INTO `detailscommandefournisseur` (`id_details_commande`, `id_commande`, `id_produit`, `quantite`, `prix_unit`) VALUES
(1, 1, 2, 2000, 450.00);

-- --------------------------------------------------------

--
-- Structure de la table `detailsvente`
--

CREATE TABLE `detailsvente` (
  `id_details_vente` int NOT NULL,
  `id_vente` int NOT NULL,
  `id_produit` int NOT NULL,
  `quantite` int NOT NULL,
  `prix_unit` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `fournisseur`
--

CREATE TABLE `fournisseur` (
  `id_fournisseur` int NOT NULL,
  `nom` varchar(255) NOT NULL,
  `adresse` text,
  `telephone` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `fournisseur`
--

INSERT INTO `fournisseur` (`id_fournisseur`, `nom`, `adresse`, `telephone`, `email`) VALUES
(1, 'SIMO', 'mimboman', '656774288', 'hedric2002@gmail.com');

-- --------------------------------------------------------

--
-- Structure de la table `livraisonclient`
--

CREATE TABLE `livraisonclient` (
  `id_livraison` int NOT NULL,
  `id_commande` int NOT NULL,
  `date_livraison` date NOT NULL,
  `statut` enum('En cours','Livrée','Annulée') DEFAULT 'En cours'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `livraisonclient`
--

INSERT INTO `livraisonclient` (`id_livraison`, `id_commande`, `date_livraison`, `statut`) VALUES
(1, 1, '2024-11-30', 'Livrée'),
(2, 2, '2024-11-30', 'Livrée');

-- --------------------------------------------------------

--
-- Structure de la table `paiement`
--

CREATE TABLE `paiement` (
  `id_paiement` int NOT NULL,
  `id_commande` int DEFAULT NULL,
  `id_vente` int DEFAULT NULL,
  `montant` decimal(10,2) NOT NULL,
  `date_paiement` date NOT NULL,
  `mode_paiement` enum('Espèces','Carte','Mobile Money') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `produit`
--

CREATE TABLE `produit` (
  `id_produit` int NOT NULL,
  `id_categorie` int NOT NULL,
  `id_depot` int NOT NULL,
  `nom` varchar(255) NOT NULL,
  `volume` int NOT NULL,
  `prix_unit` decimal(10,2) NOT NULL,
  `quantite_stock` int NOT NULL,
  `description` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `produit`
--

INSERT INTO `produit` (`id_produit`, `id_categorie`, `id_depot`, `nom`, `volume`, `prix_unit`, `quantite_stock`, `description`) VALUES
(2, 3, 1, 'Planète Pomme', 1, 450.00, 1910, '');

-- --------------------------------------------------------

--
-- Structure de la table `utilisateur`
--

CREATE TABLE `utilisateur` (
  `id_utilisateur` int NOT NULL,
  `nom` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `role` enum('Admin','Client','Employé') DEFAULT 'Client',
  `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `utilisateur`
--

INSERT INTO `utilisateur` (`id_utilisateur`, `nom`, `email`, `mot_de_passe`, `telephone`, `role`, `date_creation`) VALUES
(1, 'TAMBO SIMO Hedric', 'simohedric2023@gmail.com', '$2y$10$K6b8O3pg8zGBYLDyKG7h..j7Ton32nd7ETMadBFKKkZwnQ3HtmbHi', '656774288', 'Admin', '2024-11-29 08:32:12'),
(5, 'Meka Kengne Pascale Ariel', 'mekapascale2006@gmail.com', '$2y$10$cpoiEesFiA/PYPwa4IxsGuFgnR5NfX0bShEs1qDWLhrI/jOFUU7Ou', '656774288', 'Client', '2024-11-29 22:16:38');

-- --------------------------------------------------------

--
-- Structure de la table `vente`
--

CREATE TABLE `vente` (
  `id_vente` int NOT NULL,
  `id_client` int NOT NULL,
  `date_vente` date NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `statut` enum('Payée','En attente','Annulée') DEFAULT 'En attente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `categorie`
--
ALTER TABLE `categorie`
  ADD PRIMARY KEY (`id_categorie`);

--
-- Index pour la table `client`
--
ALTER TABLE `client`
  ADD PRIMARY KEY (`id_client`),
  ADD KEY `id_utilisateur` (`id_utilisateur`);

--
-- Index pour la table `commandeclient`
--
ALTER TABLE `commandeclient`
  ADD PRIMARY KEY (`id_commande`),
  ADD KEY `id_client` (`id_client`),
  ADD KEY `id_utilisateur` (`id_utilisateur`);

--
-- Index pour la table `commandefournisseur`
--
ALTER TABLE `commandefournisseur`
  ADD PRIMARY KEY (`id_commande`),
  ADD KEY `id_fournisseur` (`id_fournisseur`);

--
-- Index pour la table `depots`
--
ALTER TABLE `depots`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_proprietaire` (`id_proprietaire`);

--
-- Index pour la table `detailscommandeclient`
--
ALTER TABLE `detailscommandeclient`
  ADD PRIMARY KEY (`id_details_commande`),
  ADD KEY `id_commande` (`id_commande`),
  ADD KEY `id_produit` (`id_produit`);

--
-- Index pour la table `detailscommandefournisseur`
--
ALTER TABLE `detailscommandefournisseur`
  ADD PRIMARY KEY (`id_details_commande`),
  ADD KEY `id_commande` (`id_commande`),
  ADD KEY `id_produit` (`id_produit`);

--
-- Index pour la table `detailsvente`
--
ALTER TABLE `detailsvente`
  ADD PRIMARY KEY (`id_details_vente`),
  ADD KEY `id_vente` (`id_vente`),
  ADD KEY `id_produit` (`id_produit`);

--
-- Index pour la table `fournisseur`
--
ALTER TABLE `fournisseur`
  ADD PRIMARY KEY (`id_fournisseur`);

--
-- Index pour la table `livraisonclient`
--
ALTER TABLE `livraisonclient`
  ADD PRIMARY KEY (`id_livraison`),
  ADD KEY `id_commande` (`id_commande`);

--
-- Index pour la table `paiement`
--
ALTER TABLE `paiement`
  ADD PRIMARY KEY (`id_paiement`),
  ADD KEY `id_commande` (`id_commande`),
  ADD KEY `id_vente` (`id_vente`);

--
-- Index pour la table `produit`
--
ALTER TABLE `produit`
  ADD PRIMARY KEY (`id_produit`),
  ADD KEY `id_categorie` (`id_categorie`),
  ADD KEY `produit_ibfk_2` (`id_depot`);

--
-- Index pour la table `utilisateur`
--
ALTER TABLE `utilisateur`
  ADD PRIMARY KEY (`id_utilisateur`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Index pour la table `vente`
--
ALTER TABLE `vente`
  ADD PRIMARY KEY (`id_vente`),
  ADD KEY `id_client` (`id_client`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `categorie`
--
ALTER TABLE `categorie`
  MODIFY `id_categorie` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT pour la table `client`
--
ALTER TABLE `client`
  MODIFY `id_client` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `commandeclient`
--
ALTER TABLE `commandeclient`
  MODIFY `id_commande` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `commandefournisseur`
--
ALTER TABLE `commandefournisseur`
  MODIFY `id_commande` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `depots`
--
ALTER TABLE `depots`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `detailscommandeclient`
--
ALTER TABLE `detailscommandeclient`
  MODIFY `id_details_commande` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `detailscommandefournisseur`
--
ALTER TABLE `detailscommandefournisseur`
  MODIFY `id_details_commande` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `detailsvente`
--
ALTER TABLE `detailsvente`
  MODIFY `id_details_vente` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `fournisseur`
--
ALTER TABLE `fournisseur`
  MODIFY `id_fournisseur` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `livraisonclient`
--
ALTER TABLE `livraisonclient`
  MODIFY `id_livraison` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `paiement`
--
ALTER TABLE `paiement`
  MODIFY `id_paiement` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `produit`
--
ALTER TABLE `produit`
  MODIFY `id_produit` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `utilisateur`
--
ALTER TABLE `utilisateur`
  MODIFY `id_utilisateur` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `vente`
--
ALTER TABLE `vente`
  MODIFY `id_vente` int NOT NULL AUTO_INCREMENT;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `client`
--
ALTER TABLE `client`
  ADD CONSTRAINT `client_ibfk_1` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateur` (`id_utilisateur`);

--
-- Contraintes pour la table `commandeclient`
--
ALTER TABLE `commandeclient`
  ADD CONSTRAINT `commandeclient_ibfk_1` FOREIGN KEY (`id_client`) REFERENCES `client` (`id_client`),
  ADD CONSTRAINT `commandeclient_ibfk_2` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateur` (`id_utilisateur`);

--
-- Contraintes pour la table `commandefournisseur`
--
ALTER TABLE `commandefournisseur`
  ADD CONSTRAINT `commandefournisseur_ibfk_1` FOREIGN KEY (`id_fournisseur`) REFERENCES `fournisseur` (`id_fournisseur`);

--
-- Contraintes pour la table `depots`
--
ALTER TABLE `depots`
  ADD CONSTRAINT `depots_ibfk_1` FOREIGN KEY (`id_proprietaire`) REFERENCES `utilisateur` (`id_utilisateur`);

--
-- Contraintes pour la table `detailscommandeclient`
--
ALTER TABLE `detailscommandeclient`
  ADD CONSTRAINT `detailscommandeclient_ibfk_1` FOREIGN KEY (`id_commande`) REFERENCES `commandeclient` (`id_commande`),
  ADD CONSTRAINT `detailscommandeclient_ibfk_2` FOREIGN KEY (`id_produit`) REFERENCES `produit` (`id_produit`);

--
-- Contraintes pour la table `detailscommandefournisseur`
--
ALTER TABLE `detailscommandefournisseur`
  ADD CONSTRAINT `detailscommandefournisseur_ibfk_1` FOREIGN KEY (`id_commande`) REFERENCES `commandefournisseur` (`id_commande`),
  ADD CONSTRAINT `detailscommandefournisseur_ibfk_2` FOREIGN KEY (`id_produit`) REFERENCES `produit` (`id_produit`);

--
-- Contraintes pour la table `detailsvente`
--
ALTER TABLE `detailsvente`
  ADD CONSTRAINT `detailsvente_ibfk_1` FOREIGN KEY (`id_vente`) REFERENCES `vente` (`id_vente`),
  ADD CONSTRAINT `detailsvente_ibfk_2` FOREIGN KEY (`id_produit`) REFERENCES `produit` (`id_produit`);

--
-- Contraintes pour la table `livraisonclient`
--
ALTER TABLE `livraisonclient`
  ADD CONSTRAINT `livraisonclient_ibfk_1` FOREIGN KEY (`id_commande`) REFERENCES `commandeclient` (`id_commande`);

--
-- Contraintes pour la table `paiement`
--
ALTER TABLE `paiement`
  ADD CONSTRAINT `paiement_ibfk_1` FOREIGN KEY (`id_commande`) REFERENCES `commandeclient` (`id_commande`),
  ADD CONSTRAINT `paiement_ibfk_2` FOREIGN KEY (`id_vente`) REFERENCES `vente` (`id_vente`);

--
-- Contraintes pour la table `produit`
--
ALTER TABLE `produit`
  ADD CONSTRAINT `produit_ibfk_2` FOREIGN KEY (`id_depot`) REFERENCES `depots` (`id`);

--
-- Contraintes pour la table `vente`
--
ALTER TABLE `vente`
  ADD CONSTRAINT `vente_ibfk_1` FOREIGN KEY (`id_client`) REFERENCES `client` (`id_client`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
