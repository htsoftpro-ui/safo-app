# About the Dataset
This dataset contains 104 Arabic poems with their meters and patterns. A dummy "pad" column is added at the end of each poem to ensure uniform row length, which facilitates the creation of images with consistent dimensions.

# Filename Mapping
The mapping between the filenames, poets, and poems is available in:
  - `poet_poem_filename.csv`
  - `poet_poem_filename.xlsx`

# Data Collection Methodology
Poems are converted from text to syllables. After meter detection, the syllables are converted to patterns using the Arabic Meter Identification System (AMIS).

# Description of the data
Column name: METER
Column description: name of the meter
Column data type: Categorical

Column name: Ptr1
Column description: pattern 1st position
Column data type: Categorical

Column name: Ptr2
Column description: pattern 2nd position
Column data type: Categorical

Column name: Ptr3
Column description: pattern 3rd position
Column data type: Categorical

Column name: Ptr4
Column description: pattern 4th position
Column data type: Categorical

Column name: Ptr5
Column description: pattern 5th position
Column data type: Categorical

Column name: Ptr6
Column description: pattern 6th position
Column data type: Categorical

Column name: Ptr7
Column description: pattern 7th position
Column data type: Categorical

Column name: Ptr8
Column description: pattern 8th position
Column data type: Categorical

Column name: pad
Column description: dummy column with variable width
Column data type: Categorical

# File Formats
The corpus is provided in comma separated value format (CSV). Files are encoded in UTF8.

# Online Repository
https://zenodo.org/doi/10.5281/zenodo.8256824

# Author
Abdelmalek Berkani, University of Neuchâtel, Switzerland

# Related papers
Abdelmalek Berkani, Adrian Holzer, and Kilian Stoffel. Pattern matching in meter detection of arabic classical poetry. In 2020 IEEE/ACS 17th International Conference on Computer Systems and Applications (AICCSA), pages 1–8. IEEE, 2020.

Abdelmalek Berkani and Adrian Holzer. Clustering analysis of metered Arabic poetry compositions. In 2023 IEEE/ACS 20th International Conference on Computer Systems and Applications (AICCSA), pages 1–8. IEEE, 2023.


