# About Dataset
This dataset is used for meter detection of Arabic poetry. It contains 23518 distinct patterns combination.  

# About Data collection methodology
This corpus was manualy built by including all known meters in metered Arabic poetry in the litterature.

# Description of the data
Column name: ID
Column description: unique identifier
Column data type: Numeric
Distinct values: 23518
Null values: 0

Column name: METER
Column description: name of the meter variant
Column data type: Categorical
Distinct values: 29
Null values: 0

#First part (first hemistich) of the poetry verse 
Column name: FP1P
Column description: pattern name at the 1st position of the the first part of the verse
Column data type: Categorical
Distinct values: 18
Null values: 0

Column name: FP1S
Column description: syllables sequence of FP1P
Column data type: Categorical
Distinct values: 18
Null values: 0

Column name: FP2P
Column description: pattern name at the 2nd position of the the first part of the verse
Column data type: Categorical
Distinct values: 38
Null values: 0

Column name: FP2S
Column description: syllables sequence of FP2P
Column data type: Categorical
Distinct values: 38
Null values: 0

Column name: FP3P
Column description: pattern name at the 3rd position of the the first part of the verse
Column data type: Categorical
Distinct values: 32
Null values: 1960

Column name: FP3S
Column description: syllables sequence of FP3P
Column data type: Categorical
Distinct values: 32
Null values: 1960

Column name: FP4P
Column description: pattern name at the 4th position of the the first part of the verse
Column data type: Categorical
Distinct values: 15
Null values: 18632

Column name: FP4S
Column description: syllables sequence of FP4P
Column data type: Categorical
Distinct values: 15
Null values: 18632

#Second part (second hemistich) of the poetry verse
Column name: SP1P
Column description: pattern name at the 1st position of the the second part of the verse
Column data type: Categorical
Distinct values: 18
Null values: 132

Column name: SP1S
Column description: syllables sequence of SP1P
Column data type: Categorical
Distinct values: 18
Null values: 132

Column name: SP2P
Column description: pattern name at the 2nd position of the the second part of the verse
Column data type: Categorical
Distinct values: 27
Null values: 132

Column name: SP2S
Column description: syllables sequence of SP2P
Column data type: Categorical
Distinct values: 27
Null values: 132

Column name: SP3P
Column description: pattern name at the 3rd position of the the second part of the verse
Column data type: Categorical
Distinct values: 22
Null values: 2072

Column name: SP3S
Column description: syllables sequence of SP3P
Column data type: Categorical
Distinct values: 22
Null values: 2072

Column name: SP4P
Column description: pattern name at the 4th position of the the second part of the verse
Column data type: Categorical
Distinct values: 9
Null values: 18632

Column name: SP4S
Column description: syllables sequence of SP4P
Column data type: Categorical
Distinct values: 9
Null values: 18632

#Files formats
The dataset is provided in comma separated value format (CSV) and Excel format (xlsx). Files are encoded in UTF8.

# Online Repository
https://zenodo.org/doi/10.5281/zenodo.8256824

# Author
Abdelmalek Berkani, University of Neuchâtel, Switzerland

# Related paper
Abdelmalek Berkani, Adrian Holzer, and Kilian Stoffel. Pattern matching in meter detection of arabic classical poetry. In 2020 IEEE/ACS 17th International Conference on Computer Systems and Applications (AICCSA), pages 1–8. IEEE, 2020.

