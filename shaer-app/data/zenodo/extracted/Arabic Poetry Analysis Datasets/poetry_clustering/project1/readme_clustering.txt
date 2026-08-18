# About Dataset
This dataset contains the initial clustering results used for the paper "Clustering analysis of metered Arabic poetry compositions". There are 5 files, one per attributes combinations.

results_2_attribs_k2-9.csv: clustering results of 2 attributes combinations.
results_3_attribs_k2-9.csv: clustering results of 3 attributes combinations.
results_4_attribs_k2-9.csv: clustering results of 4 attributes combinations.
results_5_attribs_k2-9.csv: clustering results of 5 attributes combinations.
results_6_attribs_k2-9.csv: clustering results of 6 attributes combinations.


# About Data collection methodology
This dataset contains the clustering results obtained by R experiments.

# Description of the data. More details are described in the article itself.
Column name: fun
Column description: the clustering algorithm
Column data type: Categorical

Column name: metric
Column description: clustering distance measure, always "euclidean"
Column data type: Categorical

Column name: method
Column description: agglomeration method, always ward.D2
Column data type: Categorical

Column name: attributes
Column description: attributes combination
Column data type: String

Column name: k
Column description: number of clusters
Column data type: Numeric, discrete, ranges between 2 and 9

Column name: hopkins
Column description: hopkins statistic for cluster tendency
Column data type: Numeric

Column name: dunn
Column description: dunn index for cluster validation
Column data type: Numeric

Column name: silhouette
Column description: silhouette index for cluster validation
Column data type: Numeric

Column name: calinhara
Column description: Calinski-Harabasz index for cluster validation
Column data type: Numeric

Column name: davisbouldin
Column description: Davis-Bouldin index for cluster validation
Column data type: Numeric

Column name: bad
Column description: number of misclassified poems
Column data type: Numeric

# Files formats
The corpus is provided in comma separated value format (CSV). Files are encoded in UTF8.

# Online Repository
https://zenodo.org/doi/10.5281/zenodo.8256824

# Author
Abdelmalek Berkani, University of Neuchâtel, Switzerland

# Related paper
Abdelmalek Berkani and Adrian Holzer. Clustering analysis of metered Arabic poetry compositions. In 2023 IEEE/ACS 20th International Conference on Computer Systems and Applications (AICCSA), pages 1–8. IEEE, 2023.

