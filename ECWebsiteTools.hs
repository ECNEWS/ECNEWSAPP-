{-
🌐 ECWebsiteTools.hs – EC Website Development Tools (Haskell Edition)
📌 Run this file using:
    runhaskell ECWebsiteTools.hs
    (Make sure Haskell Platform is installed: https://www.haskell.org/platform/)
-}

module Main where

import System.Random (randomRIO)
import Data.List (isInfixOf)

-- 1) Web Analytics Tool (Page View Tracker)
type Page = String
type Views = Int

trackViews :: [(Page, Views)] -> Page -> [(Page, Views)]
trackViews [] page = [(page, 1)]
trackViews ((p, v):xs) page
  | p == page = (p, v + 1) : xs
  | otherwise = (p, v) : trackViews xs page

-- 2) A/B Testing Tool (Random User Assignment)
data Variant = A | B deriving (Show, Eq)

assignVariant :: IO Variant
assignVariant = do
  num <- randomRIO (0, 1) :: IO Int
  return $ if num == 0 then A else B

-- 3) Data Visualization Tool (Simple Bar Chart)
displayBar :: String -> Int -> IO ()
displayBar label value = putStrLn (label ++ " | " ++ replicate value '*')

drawChart :: [(String, Int)] -> IO ()
drawChart = mapM_ (uncurry displayBar)

-- 4) Website Optimization Tool (Average Load Time)
optimizeLoadTime :: [Double] -> Double
optimizeLoadTime times = sum times / fromIntegral (length times)

-- 5) SEO Tool (Keyword Analyzer)
keywordCheck :: [String] -> String -> [String]
keywordCheck keywords content = filter (`isInfixOf` content) keywords

-- 6) Social Media Tool (Post Scheduler)
type Post = (String, String) -- (Time, Message)

schedulePost :: [Post] -> Post -> [Post]
schedulePost posts newPost = posts ++ [newPost]

-- Main function to demonstrate usage of all tools
main :: IO ()
main = do
  putStrLn "\n🌐 EC Website Tools – Powered by Haskell 💻"
  putStrLn "\n--- 1) Web Analytics ---"
  let pages = [("home", 5), ("contact", 2)]
  let updated = trackViews pages "home"
  print updated

  putStrLn "\n--- 2) A/B Testing ---"
  variant <- assignVariant
  putStrLn $ "User assigned to variant: " ++ show variant

  putStrLn "\n--- 3) Data Visualization ---"
  drawChart [("Visitors", 10), ("Signups", 3), ("Sales", 1)]

  putStrLn "\n--- 4) Website Optimization ---"
  let avgLoad = optimizeLoadTime [1.2, 1.5, 1.0, 0.9]
  putStrLn $ "Average Page Load Time: " ++ show avgLoad ++ " sec"

  putStrLn "\n--- 5) SEO Tool ---"
  let foundKeywords = keywordCheck ["Haskell", "SEO", "tool", "website"] "Our SEO tool is built using Haskell for website optimization"
  putStrLn $ "Keywords found: " ++ show foundKeywords

  putStrLn "\n--- 6) Social Media Scheduler ---"
  let postList = schedulePost [] ("10:00 AM", "Good Morning from EC News!")
  putStrLn $ "Scheduled Posts: " ++ show postList
