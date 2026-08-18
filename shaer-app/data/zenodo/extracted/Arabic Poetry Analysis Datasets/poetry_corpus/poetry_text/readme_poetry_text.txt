# About Dataset
This dataset contains diacritized metered arabic poetry poems.

The folder "current" contains the most up to date content. It is enriched on a continuous basis. It contains, at the writing of this document, 3450 verses.

The folder "version 01" is a subset of the "current" content for research reproductibility reasons. It was used for the paper "Pattern matching in meter detection of Arabic classical poetry". It contains 2711 verses.

The folder "version 02" is a subset of the "current" content for research reproductibility reasons. It was used for the paper "Clustering analysis of metered Arabic poetry compositions". It contains 3381 verses.


# About Data collection methodology
This corpus was manualy built using variuous available sources on the Internet or arabic books.

# Description of the data
Column name: POET
Column description: name of the poet
Column data type: Categorical

Column name: POEM
Column description: name of the meter variant
Column data type: Categorical

Column name: VERSE
Column description: verse order in the poem
Column data type: Numeric

Column name: PART1
Column description: the first hemistich of the verse
Column data type: arabic string

Column name: PART2
Column description: the second hemistich of the verse
Column data type: arabic string

Column name: ERA
Column description: historicla period, ranges from Pre-Islamic period to nowadays
Column data type: Categorical

Column name: POEM_METER
Column description: the announced meter for the whole poem. In litterature, the poem meter is the meter of the first verse. Thus, this column has the same value per poem.
Column data type: Categorical

Column name: VERSE_METER
Column description: the real detected meter of the verse. There may be differences between the announced poem and the real meter.
Column data type: Categorical


# Files formats
The corpus is provided in comma separated value format (CSV) and Excel format (xlsx). Files are encoded in UTF8.

# Online Repository
https://zenodo.org/doi/10.5281/zenodo.8256824

# Author
Abdelmalek Berkani, University of Neuchâtel, Switzerland

# Related papers
Abdelmalek Berkani, Adrian Holzer, and Kilian Stoffel. Pattern matching in meter detection of arabic classical poetry. In 2020 IEEE/ACS 17th International Conference on Computer Systems and Applications (AICCSA), pages 1–8. IEEE, 2020.

Abdelmalek Berkani and Adrian Holzer. Clustering analysis of metered Arabic poetry compositions. In 2023 IEEE/ACS 20th International Conference on Computer Systems and Applications (AICCSA), pages 1–8. IEEE, 2023.

