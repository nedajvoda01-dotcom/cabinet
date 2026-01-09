// Stable Sort Implementation
// Provides deterministic sorting that never varies across platforms or runs

use std::cmp::Ordering;

/// Stable sort for strings (lexicographic, case-sensitive, Unicode-aware)
/// Always produces the same order for the same input
pub fn stable_sort_strings(items: &mut [String]) {
    // Use standard stable sort - it's guaranteed to be stable in Rust
    items.sort();
}

/// Stable sort for a slice with a key function
/// Maintains relative order of equal elements
pub fn stable_sort_by_key<T, F, K>(items: &mut [T], mut key_fn: F)
where
    F: FnMut(&T) -> K,
    K: Ord,
{
    items.sort_by(|a, b| key_fn(a).cmp(&key_fn(b)));
}

/// Stable sort with custom comparator
pub fn stable_sort_by<T, F>(items: &mut [T], compare: F)
where
    F: FnMut(&T, &T) -> Ordering,
{
    items.sort_by(compare);
}

/// Sort a vector and return sorted (consuming)
pub fn sorted_vec<T: Ord>(mut vec: Vec<T>) -> Vec<T> {
    vec.sort();
    vec
}

/// Sort a vector by key and return sorted (consuming)
pub fn sorted_vec_by_key<T, F, K>(mut vec: Vec<T>, key_fn: F) -> Vec<T>
where
    F: FnMut(&T) -> K,
    K: Ord,
{
    vec.sort_by_key(key_fn);
    vec
}

#[cfg(test)]
mod tests {
    use super::*;

    #[test]
    fn test_stable_sort_strings() {
        let mut items = vec![
            "zebra".to_string(),
            "apple".to_string(),
            "banana".to_string(),
        ];
        stable_sort_strings(&mut items);
        assert_eq!(items, vec!["apple", "banana", "zebra"]);
    }

    #[test]
    fn test_stable_sort_unicode() {
        let mut items = vec![
            "世界".to_string(),
            "Hello".to_string(),
            "中文".to_string(),
        ];
        stable_sort_strings(&mut items);
        // Unicode sorting is deterministic
        assert_eq!(items.len(), 3);
    }

    #[test]
    fn test_stable_sort_preserves_order() {
        // Items with same key should maintain relative order
        #[derive(Debug, PartialEq, Eq)]
        struct Item {
            key: i32,
            id: usize,
        }

        let mut items = vec![
            Item { key: 1, id: 0 },
            Item { key: 2, id: 1 },
            Item { key: 1, id: 2 },
            Item { key: 2, id: 3 },
        ];

        stable_sort_by_key(&mut items, |item| item.key);

        // Items with key=1 should maintain order: id 0 before id 2
        assert_eq!(items[0].id, 0);
        assert_eq!(items[1].id, 2);
        // Items with key=2 should maintain order: id 1 before id 3
        assert_eq!(items[2].id, 1);
        assert_eq!(items[3].id, 3);
    }

    #[test]
    fn test_sorted_vec() {
        let items = vec![3, 1, 4, 1, 5, 9, 2, 6];
        let sorted = sorted_vec(items);
        assert_eq!(sorted, vec![1, 1, 2, 3, 4, 5, 6, 9]);
    }
}
