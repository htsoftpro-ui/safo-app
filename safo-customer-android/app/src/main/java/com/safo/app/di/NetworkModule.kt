package com.safo.app.di

import com.google.gson.GsonBuilder
import com.safo.app.data.api.SafoApi
import com.safo.app.data.repository.TokenManager
import dagger.Module
import dagger.Provides
import dagger.hilt.InstallIn
import dagger.hilt.components.SingletonComponent
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.runBlocking
import okhttp3.Interceptor
import okhttp3.OkHttpClient
import okhttp3.logging.HttpLoggingInterceptor
import retrofit2.Retrofit
import retrofit2.converter.gson.GsonConverterFactory
import java.util.concurrent.TimeUnit
import javax.inject.Singleton

@Module
@InstallIn(SingletonComponent::class)
object NetworkModule {

    // Production URL — change this to your actual server
    // For emulator: http://10.0.2.2:8000
    // For production: https://your-domain.com
    private const val BASE_URL = "https://safo-app.up.railway.app/api/v1/"

    @Provides
    @Singleton
    fun provideOkHttpClient(tokenManager: TokenManager): OkHttpClient {
        val logging = HttpLoggingInterceptor().apply {
            level = HttpLoggingInterceptor.Level.BODY
        }

        return OkHttpClient.Builder()
            .addInterceptor(Interceptor { chain ->
                val token = runBlocking { tokenManager.token.first() }
                val request = chain.request().newBuilder().apply {
                    addHeader("Accept", "application/json")
                    addHeader("Content-Type", "application/json")
                    token?.let { addHeader("Authorization", "Bearer $it") }
                }.build()
                chain.proceed(request)
            })
            .addInterceptor(logging)
            .connectTimeout(30, TimeUnit.SECONDS)
            .readTimeout(30, TimeUnit.SECONDS)
            .writeTimeout(30, TimeUnit.SECONDS)
            .build()
    }

    @Provides
    @Singleton
    fun provideRetrofit(okHttpClient: OkHttpClient): Retrofit {
        return Retrofit.Builder()
            .baseUrl(BASE_URL)
            .client(okHttpClient)
            .addConverterFactory(GsonConverterFactory.create(
                GsonBuilder().setLenient().create()
            ))
            .build()
    }

    @Provides
    @Singleton
    fun provideApi(retrofit: Retrofit): SafoApi {
        return retrofit.create(SafoApi::class.java)
    }
}
