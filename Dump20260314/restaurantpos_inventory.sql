-- MySQL dump 10.13  Distrib 8.0.45, for Win64 (x86_64)
--
-- Host: localhost    Database: restaurantpos
-- ------------------------------------------------------
-- Server version	8.0.44

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `inventory`
--

DROP TABLE IF EXISTS `inventory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory` (
  `InventoryID` int NOT NULL AUTO_INCREMENT,
  `ItemName` varchar(100) NOT NULL,
  `Description` varchar(255) DEFAULT NULL,
  `Quantity` decimal(10,2) NOT NULL,
  `Unit` varchar(20) NOT NULL,
  `ReorderLevel` decimal(10,2) DEFAULT NULL,
  `CostPerUnit` decimal(10,2) DEFAULT NULL,
  `Supplier` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`InventoryID`)
) ENGINE=MyISAM AUTO_INCREMENT=206 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory`
--

LOCK TABLES `inventory` WRITE;
/*!40000 ALTER TABLE `inventory` DISABLE KEYS */;
INSERT INTO `inventory` VALUES (1,'Chicken Breast','Boneless skinless chicken',50.00,'kg',10.00,180.00,'FreshMeats Inc.'),(2,'Rice','Jasmine rice 25kg sack',20.00,'sack',5.00,1100.00,'Golden Grains'),(29,'Chicken Breast','Boneless skinless chicken',50.00,'kg',10.00,180.00,'FreshMeats Inc.'),(100,'Chicken Breast','Boneless skinless chicken',50.00,'kg',10.00,180.00,'FreshMeats Inc.'),(22,'Rice','Jasmine rice 25kg sack',20.00,'sack',5.00,1100.00,'Golden Grains'),(21,'Cooking Oil','Palm oil 5L container',30.00,'container',8.00,350.00,'Oil Depot'),(4,'Beef Patty','Frozen burger patties',100.00,'pcs',20.00,45.00,'Meat World'),(5,'Lettuce','Fresh iceberg lettuce',40.00,'head',10.00,25.00,'Veggie Farm'),(200,'Chicken Breast','Boneless skinless chicken',50.00,'kg',10.00,180.00,'FreshMeats Inc.'),(101,'Rice','Jasmine rice 25kg sack',20.00,'sack',5.00,1100.00,'Golden Grains'),(102,'Cooking Oil','Palm oil 5L container',30.00,'container',8.00,350.00,'Oil Depot'),(103,'Beef Patty','Frozen burger patties',100.00,'pcs',20.00,45.00,'Meat World'),(104,'Lettuce','Fresh iceberg lettuce',40.00,'head',10.00,25.00,'Veggie Farm'),(105,'Tomato','Fresh ripe tomatoes',60.00,'kg',15.00,35.00,'Veggie Farm'),(106,'Cheddar Cheese','Sliced cheese for burgers',200.00,'slice',50.00,5.00,'Dairy Goods Co.'),(107,'Burger Bun','Sesame burger buns',100.00,'pcs',20.00,8.00,'Baker Bros.'),(108,'Soda (Coke)','330ml canned Coke',120.00,'can',30.00,20.00,'Beverage Solutions'),(109,'Spaghetti Noodles','500g pasta packs',60.00,'pack',15.00,45.00,'Pasta World'),(110,'Tomato Sauce','Spaghetti sauce 1L',40.00,'bottle',10.00,55.00,'Sauce Factory'),(111,'Hotdog','Regular hotdogs',80.00,'pcs',20.00,12.00,'Meat World'),(112,'Fries (Frozen)','Frozen French fries',70.00,'kg',15.00,95.00,'Snack Supplies'),(113,'Salt','Refined iodized salt 1kg',25.00,'pack',5.00,25.00,'Condiment Express'),(114,'Black Pepper','Ground black pepper 100g',15.00,'bottle',5.00,60.00,'Spice Traders'),(115,'Plastic Spoon','Disposable plastic spoons',500.00,'pcs',100.00,0.80,'QuickServe Packaging'),(116,'Paper Cup','12oz paper cups',300.00,'pcs',50.00,2.00,'QuickServe Packaging'),(117,'Mayonnaise','1L mayo container',25.00,'bottle',5.00,85.00,'Sauce Factory'),(118,'Ketchup','1L ketchup container',25.00,'bottle',5.00,70.00,'Sauce Factory'),(119,'Dishwashing Liquid','1L cleaning agent',15.00,'bottle',3.00,50.00,'CleanPro Supplies');
/*!40000 ALTER TABLE `inventory` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-03-14  9:17:12
